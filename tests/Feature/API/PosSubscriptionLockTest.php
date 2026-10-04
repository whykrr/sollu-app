<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSubscriptionLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::clearTrialCache();
    }

    public function test_pos_device_allowed_when_merchant_is_on_active_trial(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        SystemSetting::set('trial_features', [FeatureEnum::POS_CASHIER->value], 'subscription');

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Active Trial POS Merchant',
            'owner_name' => 'Owner POS',
            'email' => 'pos_active@example.com',
            'phone' => '08123456784',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);

        $outlet = Outlet::factory()->create([
            'business_id' => $business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Tablet Kasir 1',
            'device_type' => DeviceTypeEnum::POS_MOBILE,
            'serial_number' => 'SN-12345',
            'client_device_uuid' => 'uuid-device-123',
            'hardware_fingerprint' => 'hw-fingerprint-abc',
            'is_active' => true,
        ]);

        Sanctum::actingAs($device, ['*']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'uuid-device-123',
            'X-HARDWARE-SIGNATURE' => 'hw-fingerprint-abc',
        ])->getJson(route('api.pos.device.status'));

        $response->assertOk();
    }

    public function test_pos_device_rejected_with_402_when_merchant_is_expired(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        SystemSetting::set('trial_features', [FeatureEnum::POS_CASHIER->value], 'subscription');

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        // Expired business without active subscription
        $business = Business::create([
            'name' => 'Expired POS Merchant',
            'owner_name' => 'Owner Expired',
            'email' => 'pos_expired@example.com',
            'phone' => '08123456785',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDays(5),
        ]);

        $outlet = Outlet::factory()->create([
            'business_id' => $business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Tablet Kasir Expired',
            'device_type' => DeviceTypeEnum::POS_MOBILE,
            'serial_number' => 'SN-99999',
            'client_device_uuid' => 'uuid-device-999',
            'hardware_fingerprint' => 'hw-fingerprint-xyz',
            'is_active' => true,
        ]);

        Sanctum::actingAs($device, ['*']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'uuid-device-999',
            'X-HARDWARE-SIGNATURE' => 'hw-fingerprint-xyz',
        ])->getJson(route('api.pos.device.status'));

        $response->assertStatus(402);
        $response->assertJson([
            'error_code' => 'SUBSCRIPTION_EXPIRED',
        ]);
    }
}
