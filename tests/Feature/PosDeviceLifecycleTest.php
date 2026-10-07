<?php

namespace Tests\Feature;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosDeviceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $cashierUser;

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
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_active' => true,
        ]);

        // User with SETTING_DEVICE permission
        $this->user = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Owner / Admin Device',
            'email' => 'admin_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('123456'),
        ]);
        setPermissionsTeamId($this->business->id);
        $this->user->givePermissionTo(PermissionEnum::SETTING_DEVICE->value);

        // Cashier user WITHOUT SETTING_DEVICE permission
        $this->cashierUser = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Kasir Biasa',
            'email' => 'cashier_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('654321'),
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'Kasir Utama 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'is_active' => true,
        ]);
    }

    public function test_device_can_pair_with_8_digit_numeric_otp(): void
    {
        $otp = '12345678';
        Cache::put("device_otp_{$otp}", $this->device->id, now()->addMinutes(15));

        $response = $this->postJson('http://api.sollu.test/pos/device/connect', [
            'otp' => '1234-5678', // Formatted input
            'device_uuid' => 'dev-uuid-001',
            'hardware_fingerprint' => 'hw-sig-001',
            'app_version' => '1.0.0',
            'platform_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.token'));

        $this->device->refresh();
        $this->assertEquals('dev-uuid-001', $this->device->client_device_uuid);
        $this->assertNull(Cache::get("device_otp_{$otp}"));
    }

    public function test_client_unpair_fails_with_invalid_pin(): void
    {
        $this->device->update([
            'client_device_uuid' => 'dev-uuid-001',
            'hardware_fingerprint' => 'hw-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->postJson('http://api.sollu.test/pos/device/unpair', [
            'pin' => '999999',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'PIN yang dimasukkan tidak valid.');
    }

    public function test_client_unpair_fails_without_setting_device_permission(): void
    {
        $this->device->update([
            'client_device_uuid' => 'dev-uuid-001',
            'hardware_fingerprint' => 'hw-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->postJson('http://api.sollu.test/pos/device/unpair', [
            'pin' => '654321', // Cashier PIN without setting.device permission
        ]);

        $response->assertStatus(403);
    }

    public function test_client_unpair_succeeds_with_authorized_pin(): void
    {
        $this->device->update([
            'client_device_uuid' => 'dev-uuid-001',
            'hardware_fingerprint' => 'hw-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->postJson('http://api.sollu.test/pos/device/unpair', [
            'pin' => '123456', // Authorized user PIN
        ]);

        $response->assertStatus(200);

        $this->device->refresh();
        $this->assertFalse($this->device->is_active);
        $this->assertNotNull($this->device->unpaired_at);
        $this->assertEquals($this->user->id, $this->device->unpaired_by);
        $this->assertFalse($this->cacheService->isDeviceActive($this->device->id));
    }

    public function test_client_unpair_succeeds_with_authorized_user_id_without_pin(): void
    {
        $this->device->update([
            'client_device_uuid' => 'dev-uuid-002',
            'hardware_fingerprint' => 'hw-sig-002',
            'is_active' => true,
            'unpaired_at' => null,
            'unpaired_by' => null,
        ]);
        $this->cacheService->putDevice($this->device);

        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-002',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-002',
        ])->postJson('http://api.sollu.test/pos/device/unpair', [
            'user_id' => $this->user->id, // Send user_id only, no pin
        ]);

        $response->assertStatus(200);

        $this->device->refresh();
        $this->assertFalse($this->device->is_active);
        $this->assertNotNull($this->device->unpaired_at);
        $this->assertEquals($this->user->id, $this->device->unpaired_by);
        $this->assertFalse($this->cacheService->isDeviceActive($this->device->id));
    }

    public function test_web_portal_sales_setting_can_update_pos_settings(): void
    {
        $this->user->givePermissionTo(PermissionEnum::SETTING_SALES->value);

        $response = $this->actingAs($this->user, 'business')
            ->put('http://app.sollu.test/settings/sales', [
                'outlet_id' => $this->outlet->id,
                'enable_supervisor_pin' => true,
                'allow_negative_stock' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => 'true',
        ]);

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $this->outlet->id,
            'category' => 'sales',
            'key' => 'allow_negative_stock',
            'value' => 'true',
        ]);
    }
}
