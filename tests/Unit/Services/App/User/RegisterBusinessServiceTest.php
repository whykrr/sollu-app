<?php

namespace Tests\Unit\Services\App\User;

use App\Enums\RoleEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\App\User\RegisterBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegisterBusinessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OutletProvisioningService $provisioningServiceMock;

    protected RegisterBusinessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Production\RolePermissionSeeder::class);
        $this->provisioningServiceMock = Mockery::mock(OutletProvisioningService::class);
        $this->service = new RegisterBusinessService($this->provisioningServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_successfully_registers_business_outlet_and_owner(): void
    {
        // Setup business type
        $type = BusinessType::factory()->forType('coffee_shop')->create();

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->with(Mockery::type(Outlet::class))
            ->andReturnNull();

        $data = [
            'name' => 'Kopi Maju Jaya',
            'owner_name' => 'Budi Santoso',
            'outlet_name' => 'Outlet Sudirman',
            'email' => 'budi@kopimaju.test',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'password' => 'secret123',
        ];

        $result = $this->service->execute($data);

        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('business', $result);
        $this->assertArrayHasKey('outlet', $result);

        $business = $result['business'];
        $outlet = $result['outlet'];
        $user = $result['user'];

        // Assert Business
        $this->assertInstanceOf(Business::class, $business);
        $this->assertEquals('Kopi Maju Jaya', $business->name);
        $this->assertEquals('Budi Santoso', $business->owner_name);
        $this->assertEquals('budi@kopimaju.test', $business->email);
        $this->assertEquals('081234567890', $business->phone);
        $this->assertEquals('active', $business->status);
        $this->assertEquals($type->id, $business->business_type_id);
        $this->assertNotNull($business->trial_end_at);

        // Assert Outlet
        $this->assertInstanceOf(Outlet::class, $outlet);
        $this->assertEquals('Outlet Sudirman', $outlet->name);
        $this->assertTrue($outlet->is_main_outlet);
        $this->assertEquals($business->id, $outlet->business_id);

        // Assert User
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertEquals('budi@kopimaju.test', $user->email);
        $this->assertEquals('081234567890', $user->phone);
        $this->assertTrue($user->is_root_user);
        $this->assertEquals($business->id, $user->business_id);
        $this->assertTrue($user->hasRole(RoleEnum::OWNER->value));
        $this->assertTrue($user->outlets->contains($outlet->id));

        // Assert Database records
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'name' => 'Kopi Maju Jaya',
            'email' => 'budi@kopimaju.test',
        ]);

        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'name' => 'Outlet Sudirman',
            'is_main_outlet' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'budi@kopimaju.test',
            'is_root_user' => true,
        ]);

        $this->assertDatabaseHas('outlet_user', [
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_rolls_back_database_transaction_on_failure(): void
    {
        $type = BusinessType::factory()->forType('minimarket')->create();

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->andThrow(new \RuntimeException('Provisioning failed unexpectedly'));

        $data = [
            'name' => 'Toko Serba Ada',
            'owner_name' => 'Siti Aminah',
            'outlet_name' => 'Toko Cabang 1',
            'email' => 'siti@tokoserba.test',
            'phone' => '081298765432',
            'business_type_id' => $type->id,
            'password' => 'password123',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provisioning failed unexpectedly');

        try {
            $this->service->execute($data);
        } finally {
            // Verify rollback occurred: no records created in database
            $this->assertDatabaseCount('businesses', 0);
            $this->assertDatabaseCount('outlets', 0);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('outlet_user', 0);
        }
    }
}
