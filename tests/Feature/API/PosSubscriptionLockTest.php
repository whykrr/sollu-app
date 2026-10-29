<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\SubscriptionPlan;
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
            'code' => FeatureEnum::POS->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'plan_trial_pos',
            'name' => 'Plan Trial POS',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);
        $plan->systemFeatures()->attach($posFeature->id);

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS->value],
        ]);

        $business = Business::create([
            'name' => 'Active Trial POS Merchant',
            'owner_name' => 'Owner POS',
            'email' => 'pos_active@example.com',
            'phone' => '08123456784',
            'status' => 'active',
            'has_used_trial' => true,
            'business_type_id' => $businessType->id,
        ]);

        $business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'is_trial' => true,
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addDays(14),
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

    public function test_pos_device_rejected_with_403_when_outlet_is_inactive(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'plan_pos_active',
            'name' => 'Plan POS Active',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);
        $plan->systemFeatures()->attach($posFeature->id);

        $businessType = BusinessType::factory()->create([
            'code' => 'retail_inactive',
            'features' => [FeatureEnum::POS->value],
        ]);

        $business = Business::create([
            'name' => 'Active Business Inactive Outlet',
            'owner_name' => 'Owner Inactive',
            'email' => 'pos_inactive@example.com',
            'phone' => '08123456788',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);

        $business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'is_trial' => true,
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addDays(14),
        ]);

        // Inactive outlet!
        $outlet = Outlet::factory()->create([
            'business_id' => $business->id,
            'name' => 'Inactive Branch',
            'is_active' => false,
        ]);

        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Tablet Kasir Inactive Branch',
            'device_type' => DeviceTypeEnum::POS_MOBILE,
            'serial_number' => 'SN-55555',
            'client_device_uuid' => 'uuid-device-inactive',
            'hardware_fingerprint' => 'hw-fingerprint-inactive',
            'is_active' => true,
        ]);

        Sanctum::actingAs($device, ['*']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'uuid-device-inactive',
            'X-HARDWARE-SIGNATURE' => 'hw-fingerprint-inactive',
        ])->getJson(route('api.pos.device.status'));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'OUTLET_INACTIVE',
        ]);
    }

    public function test_pos_device_rejected_with_402_when_merchant_is_expired(): void
    {
        $businessType = BusinessType::factory()->create([
            'code' => 'retail_exp',
            'features' => [FeatureEnum::POS->value],
        ]);

        // Expired business without active subscription
        $business = Business::create([
            'name' => 'Expired POS Merchant',
            'owner_name' => 'Owner Expired',
            'email' => 'pos_expired@example.com',
            'phone' => '08123456785',
            'status' => 'active',
            'business_type_id' => $businessType->id,
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
