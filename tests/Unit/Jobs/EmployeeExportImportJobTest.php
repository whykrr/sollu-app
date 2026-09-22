<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Employee\ExportEmployeeJob;
use App\Jobs\Employee\ImportEmployeeJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ExcelImportCompleted;
use App\Notifications\NewEmployee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class EmployeeExportImportJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Job Test Business',
            'owner_name' => 'Business Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Owner Admin',
            'email' => 'admin_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Pusat',
            'is_main_outlet' => true,
        ]);

        $this->user->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);
        $this->role = Role::firstOrCreate(
            ['business_id' => $this->business->id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir Toko', 'is_default' => false]
        );
    }

    public function test_export_employee_job_query_and_mapping(): void
    {
        $employee = User::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Staff Kasir',
            'email' => 'kasir@test.test',
            'phone' => '08123456789',
            'is_root_user' => false,
        ]);
        $employee->assignRole('cashier');
        $employee->outlets()->attach($this->outlet->id);

        $job = new ExportEmployeeJob($this->user, [
            'search' => 'Staff Kasir',
            'role' => 'cashier',
            'outlet' => $this->outlet->id,
        ]);

        $this->assertSame('Pegawai', $job->getModuleName());
        $this->assertStringStartsWith('pegawai_export_', $job->getFileName());
        $this->assertStringEndsWith('.xlsx', $job->getFileName());

        $headers = $job->getHeaders();
        $this->assertCount(7, $headers);
        $this->assertSame('Nama Lengkap', $headers[0]);
        $this->assertSame('Akses Outlet', $headers[4]);

        $query = $job->getQuery();
        $results = $query->get();

        $this->assertCount(1, $results);
        $mapped = $job->mapRow($results->first());

        $this->assertSame('Staff Kasir', $mapped[0]);
        $this->assertSame('kasir@test.test', $mapped[1]);
        $this->assertSame('08123456789', $mapped[2]);
        $this->assertSame('Kasir Toko', $mapped[3]);
        $this->assertSame('Outlet Pusat', $mapped[4]);
        $this->assertSame('Aktif', $mapped[5]);
    }

    public function test_export_employee_job_mapping_for_owner_and_trashed(): void
    {
        $job = new ExportEmployeeJob($this->user);
        $mappedOwner = $job->mapRow($this->user);

        $this->assertSame('Pemilik Usaha (Owner)', $mappedOwner[3]);
        $this->assertSame('Semua Outlet', $mappedOwner[4]);

        $trashed = User::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Mantan Staff',
            'deleted_at' => now(),
        ]);
        $mappedTrashed = $job->mapRow($trashed);
        $this->assertSame('Arsip', $mappedTrashed[5]);
    }

    public function test_import_employee_job_executes_successfully_with_default_password_output(): void
    {
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');

        $importData = [
            ['Nama Lengkap', 'Email', 'Nomor Telepon', 'PIN (6 Angka)', 'Peran', 'Outlet'],
            ['Budi Santoso', 'budi@test.test', '081234567891', '123456', 'Kasir Toko', 'Outlet Pusat'],
            ['Siti Aminah', 'siti@test.test', '081234567892', '654321', 'cashier', ''],
            ['Invalid Role Row', 'invalid@test.test', '081234567893', '111111', 'NonExistentRole', ''],
        ];

        $exportClass = new class($importData) implements FromArray
        {
            public function __construct(private array $data) {}

            public function array(): array
            {
                return $this->data;
            }
        };

        $tempFileName = 'imports/test_employee_import.xlsx';
        Excel::store($exportClass, $tempFileName, 'local');

        $job = new ImportEmployeeJob($this->user, $tempFileName);
        $job->handle();

        // 1. Assert users created in database
        $this->assertDatabaseHas('users', [
            'business_id' => $this->business->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@test.test',
        ]);
        $this->assertDatabaseHas('users', [
            'business_id' => $this->business->id,
            'name' => 'Siti Aminah',
            'email' => 'siti@test.test',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid@test.test',
        ]);

        // 2. Assert NewEmployee notification was sent to created users
        $budiUser = User::where('email', 'budi@test.test')->first();
        $this->assertNotNull($budiUser);
        $this->assertTrue($budiUser->hasRole('cashier'));
        $this->assertCount(1, $budiUser->outlets);

        Notification::assertSentTo($budiUser, NewEmployee::class);

        // 3. Assert ExcelImportCompleted notification was sent to admin with summary and password file
        Notification::assertSentTo(
            $this->user,
            ExcelImportCompleted::class,
            function (ExcelImportCompleted $notification) {
                return $notification->meta['success_count'] === 2
                    && $notification->meta['failed_count'] === 1
                    && ! empty($notification->actionUrl)
                    && str_contains($notification->actionUrl, 'hasil_impor_pegawai_');
            }
        );
    }
}
