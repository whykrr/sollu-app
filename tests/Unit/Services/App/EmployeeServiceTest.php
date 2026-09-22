<?php

namespace Tests\Unit\Services\App;

use App\Models\Outlet;
use App\Models\User;
use App\Notifications\NewEmployee;
use App\Services\App\Employee\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->service = new EmployeeService;
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
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
        \App\Models\Role::firstOrCreate(
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
        \App\Models\Role::firstOrCreate(
            ['business_id' => $user->business_id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir', 'is_default' => false]
        );
        \App\Models\Role::firstOrCreate(
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
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('9999', $updated->pin));

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
}
