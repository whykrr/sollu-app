<?php

namespace Tests\Unit\Services\App\Outlet;

use App\Enums\DeviceTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\User;
use App\Services\App\Outlet\ManageOutletDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManageOutletDeviceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManageOutletDeviceService $service;

    protected Business $business;

    protected User $user;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ManageOutletDeviceService;

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Test',
        ]);
    }

    public function test_it_creates_device()
    {
        $data = [
            'device_name' => 'POS 1',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'serial_number' => 'SN-12345',
            'is_active' => true,
        ];

        $device = $this->service->createDevice($this->outlet, $data, $this->user);

        $this->assertInstanceOf(OutletDevice::class, $device);
        $this->assertEquals('POS 1', $device->device_name);
        $this->assertEquals(DeviceTypeEnum::POS_TERMINAL, $device->device_type);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'action' => 'device_added',
        ]);
    }

    public function test_it_enforces_single_device_per_outlet_quota_without_multi_device_feature()
    {
        // First device succeeds
        $this->service->createDevice($this->outlet, [
            'device_name' => 'POS 1',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ], $this->user);

        // Second device for same outlet should fail if tenant does not have multi_device feature
        $this->expectException(ValidationException::class);

        $this->service->createDevice($this->outlet, [
            'device_name' => 'POS 2',
            'device_type' => DeviceTypeEnum::POS_MOBILE->value,
        ], $this->user);
    }

    public function test_it_updates_device()
    {
        $device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'POS Old',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        $data = [
            'device_name' => 'POS New',
            'device_type' => DeviceTypeEnum::POS_MOBILE->value,
        ];

        $updatedDevice = $this->service->updateDevice($device, $data, $this->user);

        $this->assertEquals('POS New', $updatedDevice->device_name);
        $this->assertEquals(DeviceTypeEnum::POS_MOBILE, $updatedDevice->device_type);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'action' => 'device_updated',
        ]);
    }

    public function test_it_deletes_device()
    {
        $device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'Delete Me',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        Cache::shouldReceive('forget')->once()->with("pos_device_{$device->id}");

        $this->service->deleteDevice($device, $this->user);

        $this->assertDatabaseMissing('outlet_devices', [
            'id' => $device->id,
        ]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'action' => 'device_deleted',
        ]);
    }
}
