<?php

declare(strict_types=1);

namespace App\Services\App\Employee;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\User;
use App\Notifications\NewEmployee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeService
{
    public const INDONESIAN_CITIES = [
        'Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Medan',
        'Makassar', 'Palembang', 'Bogor', 'Batam', 'Malang',
        'Denpasar', 'Yogyakarta', 'Solo', 'Padang', 'Pontianak',
        'Banjarmasin', 'Manado', 'Cirebon', 'Bekasi', 'Tangerang',
    ];

    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    /**
     * Generate a memorable default password using an Indonesian city name and 4 random digits.
     */
    public static function generateDefaultPassword(): string
    {
        $city = self::INDONESIAN_CITIES[array_rand(self::INDONESIAN_CITIES)];
        $number = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        return "{$city}{$number}";
    }

    /**
     * Create a new employee.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function create(array $data): User
    {
        $hasWebAccess = ! empty($data['has_web_access'])
            || (! isset($data['has_web_access']) && ! empty($data['email']));

        $defaultPassword = $hasWebAccess
            ? ($data['password'] ?? self::generateDefaultPassword())
            : null;

        DB::beginTransaction();
        try {
            /** @var User $user */
            $user = Auth::user()->business->users()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $defaultPassword,
                'pin' => $data['pin'] ?? null,
                'is_root_user' => false,
                'email_verified_at' => $hasWebAccess ? Carbon::now() : null,
            ]);

            $user->assignRole($data['role']);
            $user->outlets()->attach($data['outlets']);

            $this->auditLogger->log(
                module: AuditModuleEnum::EMPLOYEES->value,
                action: 'employee.created',
                description: "Menambahkan staf baru: {$user->name}",
                subject: $user,
                causer: Auth::user(),
                properties: [
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $data['role'],
                        'has_web_access' => $hasWebAccess,
                    ],
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        if ($hasWebAccess && ! empty($defaultPassword) && ! empty($user->email)) {
            $user->notify(new NewEmployee($defaultPassword));
        }

        return $user;
    }

    /**
     * Update an existing employee.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function update(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            if (empty($data['pin'])) {
                unset($data['pin']);
            }

            // Handle transition for web access if explicitly provided
            $generatedPassword = null;
            if (isset($data['has_web_access'])) {
                if ($data['has_web_access'] && empty($user->password) && ! empty($data['email'])) {
                    $generatedPassword = self::generateDefaultPassword();
                    $data['password'] = $generatedPassword;
                    $data['email_verified_at'] = Carbon::now();
                } elseif (! $data['has_web_access'] && ! $user->is_root_user) {
                    $data['password'] = null;
                }
            }

            $user->update($data);

            if (! $user->is_root_user) {
                if (isset($data['role']) && ! $user->hasRole($data['role'])) {
                    $user->syncRoles($data['role']);
                }
                if (isset($data['outlets'])) {
                    $user->outlets()->sync($data['outlets']);
                }
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::EMPLOYEES->value,
                action: 'employee.updated',
                description: "Memperbarui data staf: {$user->name}",
                subject: $user,
                causer: Auth::user(),
                properties: [
                    'old' => $before,
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        if (! empty($generatedPassword) && ! empty($user->email)) {
            $user->notify(new NewEmployee($generatedPassword));
        }

        return $user;
    }

    /**
     * Soft delete an employee.
     *
     * @throws \Throwable
     */
    public function delete(User $user): void
    {
        if ($user->is_root_user) {
            throw new InvalidArgumentException('Akun pemilik usaha (Owner) tidak dapat dihapus.');
        }

        if (Auth::id() && $user->id === Auth::id()) {
            throw new InvalidArgumentException('Kamu tidak dapat menghapus akunmu sendiri.');
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.deleted',
            description: "Memindahkan akun staf ke sampah: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );

        $user->deleteOrFail();
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(User $user): void
    {
        $user->restore();

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.restored',
            description: "Memulihkan akun staf: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );
    }

    /**
     * Force delete an employee permanently.
     */
    public function destroy(User $user): void
    {
        if ($user->is_root_user) {
            throw new InvalidArgumentException('Akun pemilik usaha (Owner) tidak dapat dihapus.');
        }

        if (Auth::id() && $user->id === Auth::id()) {
            throw new InvalidArgumentException('Kamu tidak dapat menghapus akunmu sendiri.');
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.destroyed',
            description: "Menghapus permanen akun staf: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );

        $user->forceDelete();
    }

    /**
     * Reset an employee's password without sending email.
     * Generates a new Indonesian city + 4 digits password, saves it, and returns the plain text password.
     *
     * @throws InvalidArgumentException
     */
    public function resetPassword(User $user): string
    {
        if ($user->is_root_user) {
            throw new InvalidArgumentException('Kata sandi Akun Utama tidak dapat direset melalui menu pegawai.');
        }

        $newPassword = self::generateDefaultPassword();

        $user->update([
            'password' => $newPassword,
            'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
        ]);

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.password_reset',
            description: "Mereset kata sandi staf: {$user->name}",
            subject: $user,
            causer: Auth::user(),
            properties: [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        );

        return $newPassword;
    }
}
