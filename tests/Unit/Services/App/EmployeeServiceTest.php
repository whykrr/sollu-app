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
        $this->service = new EmployeeService;
    }

    public function test_it_creates_employee()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::first();
        $this->actingAs($user);

        Notification::fake();

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Test',
        ]);

        $data = [
            'name' => 'New Employee',
            'email' => 'employee@test.com',
            'phone' => '08123456789',
            'pin' => '1234',
            'role' => 'cashier', // Assuming 'cashier' role exists from seeder
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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::first();
        $this->actingAs($user);

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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::first();

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
        ]);

        $this->service->delete($employee);

        $this->assertSoftDeleted('users', ['id' => $employee->id]);
    }

    public function test_it_restores_employee()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::first();

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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::first();

        $employee = User::factory()->create([
            'business_id' => $user->business_id,
        ]);

        $this->service->destroy($employee);

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }
}
