<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected OutletDevice $device;

    protected PosDeviceAuthCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->cacheService = app(PosDeviceAuthCacheService::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Version Test Business',
            'owner_name' => 'Version Owner',
            'email' => 'version_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Version Outlet',
            'address' => 'Jl. Versi No. 1',
            'phone_number' => '081234567891',
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'Terminal Versi V1',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'is_active' => true,
        ]);
    }

    public function test_canonical_v1_endpoint_returns_version_header_without_deprecation(): void
    {
        $otp = '12345678';
        Cache::put("device_otp_{$otp}", $this->device->id, now()->addMinutes(15));

        $response = $this->postJson('http://api.sollu.test/v1/pos/device/connect', [
            'otp' => '12345678',
            'device_uuid' => 'dev-uuid-v1',
            'hardware_fingerprint' => 'hw-sig-v1',
            'app_version' => '1.0.0',
            'platform_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-API-Version', 'v1');
        $this->assertFalse($response->headers->has('Deprecation'));
        $this->assertFalse($response->headers->has('Sunset'));
        $this->assertFalse($response->headers->has('X-API-Deprecation-Warning'));
    }

    public function test_unversioned_fallback_endpoint_returns_rfc_deprecation_headers_and_version_header(): void
    {
        $otp = '87654321';
        Cache::put("device_otp_{$otp}", $this->device->id, now()->addMinutes(15));

        $response = $this->postJson('http://api.sollu.test/pos/device/connect', [
            'otp' => '87654321',
            'device_uuid' => 'dev-uuid-unversioned',
            'hardware_fingerprint' => 'hw-sig-unversioned',
            'app_version' => '1.0.0',
            'platform_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-API-Version', 'v1');
        $response->assertHeader('Deprecation', 'true');
        $response->assertHeader('Sunset', 'Sat, 01 Jan 2028 00:00:00 GMT');
        $response->assertHeader('Link', '<http://api.sollu.test/docs>; rel="deprecation"');
        $this->assertTrue($response->headers->has('X-API-Deprecation-Warning'));
    }

    public function test_legacy_app_domain_endpoint_returns_deprecation_and_version_headers(): void
    {
        $otp = '99887766';
        Cache::put("device_otp_{$otp}", $this->device->id, now()->addMinutes(15));

        $response = $this->postJson('http://app.sollu.test/api/pos/device/connect', [
            'otp' => '99887766',
            'device_uuid' => 'dev-uuid-legacy',
            'hardware_fingerprint' => 'hw-sig-legacy',
            'app_version' => '1.0.0',
            'platform_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-API-Version', 'v1');
        $response->assertHeader('Deprecation', 'true');
        $this->assertTrue($response->headers->has('X-API-Deprecation-Warning'));
    }

    public function test_canonical_v1_protected_endpoint_works_seamlessly(): void
    {
        $this->device->update([
            'client_device_uuid' => 'dev-uuid-v1',
            'hardware_fingerprint' => 'hw-sig-v1',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-v1',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-v1',
        ])->getJson('http://api.sollu.test/v1/pos/device/status');

        $response->assertStatus(200);
        $response->assertHeader('X-API-Version', 'v1');
        $this->assertFalse($response->headers->has('Deprecation'));
    }
}
