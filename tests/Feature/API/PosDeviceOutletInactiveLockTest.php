<?php

namespace Tests\Feature\API;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\SubscriptionPlan;
use App\Services\App\Subscription\SubscriptionService;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosDeviceOutletInactiveLockTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->apiDomain = config('domain.api', 'api.sollu.test');
    }

    public function test_pos_request_rejected_with_403_and_outlet_inactive_error_code_when_outlet_is_disabled(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Locked Outlet Store',
            'owner_name' => 'Store Owner',
            'email' => 'locked_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'micro-plan-pos',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 29000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
        ]);

        app(SubscriptionService::class)->subscribe($business, $plan, 'monthly');

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Dinonaktifkan',
            'slug' => 'cabang-dinonaktifkan',
            'is_active' => false, // Inactive outlet
        ]);

        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Kasir Tablet A',
            'device_type' => 'pos_mobile',
            'serial_number' => 'SN-'.Str::random(10),
            'client_device_uuid' => 'dev-uuid-locked',
            'hardware_fingerprint' => 'hw-sig-locked',
            'is_active' => true,
        ]);

        app(PosDeviceAuthCacheService::class)->putDevice($device);

        Sanctum::actingAs($device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-locked',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-locked',
            'Accept' => 'application/json',
        ])->getJson("http://{$this->apiDomain}/v1/pos/sync/master");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'OUTLET_INACTIVE',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_code',
        ]);
    }
}
