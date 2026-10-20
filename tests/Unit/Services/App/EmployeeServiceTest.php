<?php

namespace Tests\Unit\Services\App;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewEmployee;
use App\Services\App\Employee\EmployeeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->service = new EmployeeService;
    }

    protected function createMerchantUser(): User
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Owner Name',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Owner User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        setPermissionsTeamId($business->id);

        return $user;
    }

    public function test_it_creates_employee()
    {
        $user = $this->createMerchantUser();
        $this->actingAs($user);

        Notification::fake();

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Test',
        ]);

        setPermissionsTeamId($user->business_id);
        Role::firstOrCreate(
            ['business_id' => $user->business_id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir', 'is_default' => false]
        );

        $data = [
            'name' => 'New Employee',
            'email' => 'employee@test.com',
            'phone' => '08123456789',
            'pin' => '1234',
            'role' => 'cashier',
            'outlets' => [$outlet->id],
        ];

        $employee = $this->service->create($data);

        $this->assertInstanceOf(User::class, $employee);
        $this->assertEquals('New Employee', $employee->name);
        $this->assertFalse($employee->is_root_user);
        $this->assertTrue($employee->hasRole('cashier'));

        $this->assertCount(1, $employee->outlets);
        $this->assertEquals($outlet->id, $employee->outlets->first()->id);

        Notification::assertSentTo(
            [$employee], NewEmployee::class
        );
    }

    public function test_it_updates_employee()
    {
        $user = $this->createMerchantUser();
        $this->actingAs($user);

        setPermissionsTeamId($user->business_id);
        Role::firstOrCreate(
            ['business_id' => $user->business_id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir', 'is_default' => false]
        );
        Role::firstOrCreate(
            ['business_id' => $user->business_id, 'name' => 'manager', 'guard_name' => 'business'],
            ['label' => 'Manajer', 'is_default' => false]
        );

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
            'name' => 'Old Employee',
            'is_root_user' => false,
        ]);
        $employee->assignRole('cashier');

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Test',
        ]);

        $data = [
            'name' => 'Updated Employee',
            'role' => 'manager',
            'outlets' => [$outlet->id],
            'pin' => '9999',
        ];

        $updated = $this->service->update($employee, $data);

        $this->assertEquals('Updated Employee', $updated->name);
        $this->assertTrue(Hash::check('9999', $updated->pin));

        $updated->refresh();
        $this->assertTrue($updated->hasRole('manager'));
        $this->assertFalse($updated->hasRole('cashier'));

        $this->assertCount(1, $updated->outlets);
        $this->assertEquals($outlet->id, $updated->outlets->first()->id);
    }

    public function test_it_soft_deletes_employee()
    {
        $user = $this->createMerchantUser();

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
        ]);

        $this->service->delete($employee);

        $this->assertSoftDeleted('users', ['id' => $employee->id]);
    }

    public function test_it_restores_employee()
    {
        $user = $this->createMerchantUser();

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
            'deleted_at' => now(),
        ]);

        $this->service->restore($employee);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_force_deletes_employee()
    {
        $user = $this->createMerchantUser();

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
        ]);

        $this->service->destroy($employee);

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }

    public function test_it_prevents_deleting_root_user()
    {
        $user = $this->createMerchantUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Akun pemilik usaha (Owner) tidak dapat dihapus.');

        $this->service->delete($user);
    }

    public function test_it_prevents_deleting_self()
    {
        $user = $this->createMerchantUser();
        $this->actingAs($user);

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
            'is_root_user' => false,
        ]);
        $this->actingAs($employee);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kamu tidak dapat menghapus akunmu sendiri.');

        $this->service->delete($employee);
    }

    public function test_it_generates_default_password_using_indonesian_city_and_four_digits(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $password = EmployeeService::generateDefaultPassword();

            $this->assertGreaterThanOrEqual(8, strlen($password));

            // Must match one of the Indonesian cities followed by 4 digits
            $matchedCity = false;
            foreach (EmployeeService::INDONESIAN_CITIES as $city) {
                if (str_starts_with($password, $city)) {
                    $matchedCity = true;
                    $suffix = substr($password, strlen($city));
                    $this->assertEquals(4, strlen($suffix));
                    $this->assertTrue(ctype_digit($suffix));
                    break;
                }
            }

            $this->assertTrue($matchedCity, "Password {$password} should start with a valid Indonesian city");
        }
    }

    public function test_it_creates_cashier_without_email_and_without_password_and_no_notification(): void
    {
        $user = $this->createMerchantUser();
        $this->actingAs($user);

        Notification::fake();

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Kasir',
        ]);

        setPermissionsTeamId($user->business_id);
        Role::firstOrCreate(
            ['business_id' => $user->business_id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir', 'is_default' => false]
        );

        $data = [
            'name' => 'Kasir Murni POS',
            'email' => null,
            'phone' => null,
            'has_web_access' => false,
            'pin' => '123456',
            'role' => 'cashier',
            'outlets' => [$outlet->id],
        ];

        $employee = $this->service->create($data);

        $this->assertInstanceOf(User::class, $employee);
        $this->assertNull($employee->email);
        $this->assertNull($employee->password);
        $this->assertNull($employee->email_verified_at);
        $this->assertNotNull($employee->pin);

        Notification::assertNothingSent();
    }

    public function test_it_resets_employee_password_without_sending_email(): void
    {
        $owner = $this->createMerchantUser();
        $this->actingAs($owner);

        Notification::fake();

        $employee = User::create([
            'business_id' => $owner->business_id,
            'name' => 'Staf Test',
            'email' => 'staf@test.test',
            'password' => 'old_password_123',
            'is_root_user' => false,
        ]);

        $newPassword = $this->service->resetPassword($employee);

        $this->assertNotEmpty($newPassword);
        $this->assertTrue(Hash::check($newPassword, $employee->fresh()->password));
        $this->assertFalse(Hash::check('old_password_123', $employee->fresh()->password));

        // Ensure NO email notification is sent
        Notification::assertNothingSent();
    }

    public function test_it_prevents_resetting_root_user_password_via_employee_service(): void
    {
        $owner = $this->createMerchantUser();
        $this->actingAs($owner);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kata sandi Akun Utama tidak dapat direset melalui menu pegawai.');

        $this->service->resetPassword($owner);
    }
}
