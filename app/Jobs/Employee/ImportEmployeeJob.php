<?php

namespace App\Jobs\Employee;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ExcelImportCompleted;
use App\Notifications\NewEmployee;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ImportEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $userId;

    public ?User $user = null;

    protected string $filePath;

    public function __construct(User $user, string $filePath)
    {
        $this->user = $user;
        $this->userId = $user->id;
        $this->filePath = $filePath;
    }

    public function getModuleName(): string
    {
        return 'Pegawai';
    }

    public function handle(): void
    {
        if (! $this->user) {
            $this->user = User::find($this->userId);
        }

        if (! $this->user) {
            return;
        }

        if (! Storage::disk('local')->exists($this->filePath)) {
            return;
        }

        config(['excel.imports.heading_row.formatter' => 'none']);
        HeadingRowFormatter::reset();

        $businessId = $this->user->business_id;
        $availableRoles = Role::where('business_id', $businessId)->get();
        $availableOutlets = Outlet::where('business_id', $businessId)->get();

        $processedResults = [];
        $successCount = 0;
        $failedCount = 0;

        $importClass = new class($this, $availableRoles, $availableOutlets, $processedResults, $successCount, $failedCount) implements ToCollection, WithHeadingRow
        {
            public function __construct(
                private ImportEmployeeJob $job,
                private Collection $roles,
                private Collection $outlets,
                public array &$results,
                public int &$successCount,
                public int &$failedCount
            ) {}

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $rowData = $row->toArray();

                    if (empty(array_filter($rowData, fn ($val) => $val !== null && $val !== ''))) {
                        continue;
                    }

                    $clean = [];
                    foreach ($rowData as $k => $v) {
                        $cleanKey = is_string($k) ? trim($k) : $k;
                        $cleanVal = is_string($v) ? trim($v) : $v;
                        $clean[$cleanKey] = $cleanVal;
                    }

                    $name = $clean['Nama Lengkap'] ?? ($clean['Nama'] ?? null);
                    $email = $clean['Email'] ?? null;
                    $phone = $clean['Nomor Telepon'] ?? ($clean['Telepon'] ?? ($clean['Phone'] ?? null));
                    $pin = $clean['PIN (6 Angka)'] ?? ($clean['PIN'] ?? ($clean['Pin'] ?? null));
                    $roleInput = $clean['Peran'] ?? ($clean['Role'] ?? null);
                    $outletInput = $clean['Outlet'] ?? null;

                    try {
                        $validator = Validator::make([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'pin' => $pin,
                            'role' => $roleInput,
                        ], [
                            'name' => 'required|string|max:200',
                            'email' => 'required|email|max:200',
                            'phone' => 'nullable|numeric|digits_between:8,16',
                            'pin' => 'required|numeric|digits:6',
                            'role' => 'required|string',
                        ], [
                            'name.required' => 'Nama lengkap wajib diisi.',
                            'email.required' => 'Alamat email wajib diisi.',
                            'email.email' => 'Format email tidak valid.',
                            'pin.required' => 'PIN wajib diisi.',
                            'pin.digits' => 'PIN harus berupa 6 angka.',
                            'role.required' => 'Peran wajib diisi.',
                        ]);

                        if ($validator->fails()) {
                            throw new Exception(implode(', ', $validator->errors()->all()));
                        }

                        // Check global email uniqueness
                        if (User::withTrashed()->where('email', $email)->exists()) {
                            throw new Exception("Email '{$email}' sudah terdaftar di sistem.");
                        }

                        // Check phone uniqueness if provided
                        if (! empty($phone) && User::withTrashed()->where('phone', $phone)->exists()) {
                            throw new Exception("Nomor telepon '{$phone}' sudah digunakan.");
                        }

                        // Resolve Role
                        $matchedRole = $this->roles->first(function (Role $r) use ($roleInput) {
                            return strcasecmp($r->name, $roleInput) === 0
                                || strcasecmp($r->label ?? '', $roleInput) === 0;
                        });

                        if (! $matchedRole) {
                            $validRoles = $this->roles->map(fn ($r) => $r->label ?? $r->name)->implode(', ');
                            throw new Exception("Peran '{$roleInput}' tidak ditemukan. Pilihan peran yang tersedia: {$validRoles}");
                        }

                        // Resolve Outlets
                        $assignedOutletIds = [];
                        $assignedOutletNames = [];

                        if (! empty($outletInput)) {
                            $requestedOutlets = array_map('trim', explode(',', $outletInput));
                            foreach ($requestedOutlets as $reqName) {
                                $foundOutlet = $this->outlets->first(function (Outlet $o) use ($reqName) {
                                    return strcasecmp($o->name, $reqName) === 0 || strcasecmp($o->slug, $reqName) === 0;
                                });

                                if ($foundOutlet) {
                                    $assignedOutletIds[] = $foundOutlet->id;
                                    $assignedOutletNames[] = $foundOutlet->name;
                                }
                            }
                        }

                        // If no outlets matched or none specified, assign to all available outlets or main outlet
                        if (empty($assignedOutletIds)) {
                            $assignedOutletIds = $this->outlets->pluck('id')->toArray();
                            $assignedOutletNames = $this->outlets->pluck('name')->toArray();
                        }

                        $defaultPassword = Str::random(10);

                        DB::beginTransaction();
                        try {
                            /** @var \App\Models\User $newUser */
                            $newUser = $this->job->user->business->users()->create([
                                'name' => $name,
                                'email' => $email,
                                'phone' => $phone ?: null,
                                'password' => $defaultPassword,
                                'pin' => (string) $pin,
                                'is_root_user' => false,
                                'email_verified_at' => Carbon::now(),
                            ]);

                            $newUser->assignRole($matchedRole->name);
                            if (! empty($assignedOutletIds)) {
                                $newUser->outlets()->attach($assignedOutletIds);
                            }

                            DB::commit();
                        } catch (\Throwable $ex) {
                            DB::rollBack();
                            throw $ex;
                        }

                        // Send welcome notification & credentials to the new employee
                        $newUser->notify(new NewEmployee($defaultPassword));

                        $this->results[] = [
                            'Nama Lengkap' => $name,
                            'Email' => $email,
                            'Nomor Telepon' => $phone ?? '-',
                            'PIN' => $pin,
                            'Peran' => $matchedRole->label ?? $matchedRole->name,
                            'Outlet' => implode(', ', $assignedOutletNames),
                            'Password Default' => $defaultPassword,
                            'Status' => 'Berhasil',
                            'Keterangan' => 'Akun berhasil dibuat',
                        ];
                        $this->successCount++;
                    } catch (\Throwable $e) {
                        $this->results[] = [
                            'Nama Lengkap' => $name ?? '-',
                            'Email' => $email ?? '-',
                            'Nomor Telepon' => $phone ?? '-',
                            'PIN' => $pin ?? '-',
                            'Peran' => $roleInput ?? '-',
                            'Outlet' => $outletInput ?? '-',
                            'Password Default' => '-',
                            'Status' => 'Gagal',
                            'Keterangan' => $e->getMessage(),
                        ];
                        $this->failedCount++;
                    }
                }
            }
        };

        Excel::import($importClass, Storage::disk('local')->path($this->filePath));

        Storage::disk('local')->delete($this->filePath);

        $resultUrl = null;
        if (! empty($processedResults)) {
            Storage::makeDirectory('exports');
            $resultFileName = 'hasil_impor_pegawai_'.time().'.xlsx';
            $resultFilePath = 'exports/'.$resultFileName;

            $exportResults = new class($processedResults) implements FromArray
            {
                public function __construct(private array $data) {}

                public function array(): array
                {
                    if (empty($this->data)) {
                        return [];
                    }
                    $headers = array_keys($this->data[0]);

                    return array_merge([$headers], $this->data);
                }
            };

            Excel::store($exportResults, $resultFilePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
            $resultUrl = route('exports.download', ['file' => $resultFileName]);
        }

        $this->user->notify(new ExcelImportCompleted(
            $this->getModuleName(),
            $successCount,
            $failedCount,
            $resultUrl,
            now()->addDays(7),
            'Unduh Hasil Impor & Password'
        ));
    }
}
