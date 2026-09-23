<?php

namespace Tests\Feature\App\Settings;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DeviceSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

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

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        // Assign permissions
        setPermissionsTeamId($this->business->id);
        $this->user->givePermissionTo(PermissionEnum::SETTING_DEVICE->value);
        $this->user->givePermissionTo(PermissionEnum::OUTLET_VIEW->value);

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang A',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang B',
            'is_active' => true,
        ]);

        // Use seeded basic plan (has OUTLET_MANAGEMENT, but not MULTI_DEVICE by default)
        $plan = SubscriptionPlan::where('code', 'basic')->first() ?? SubscriptionPlan::first();

        $this->business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
    }

    public function test_it_displays_all_devices_when_no_sidebar_outlet_selected()
    {
        OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir A',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'is_active' => true,
        ]);

        OutletDevice::create([
            'outlet_id' => $this->outletB->id,
            'device_name' => 'Kasir B',
            'device_type' => DeviceTypeEnum::POS_MOBILE->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/devices");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Device/Index')
            ->has('devices.data', 2)
            ->has('outlets', 2)
            ->where('selectedOutlet', null)
        );
    }

    public function test_it_filters_devices_by_outlet()
    {
        OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir A',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'is_active' => true,
        ]);

        OutletDevice::create([
            'outlet_id' => $this->outletB->id,
            'device_name' => 'Kasir B',
            'device_type' => DeviceTypeEnum::POS_MOBILE->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/devices?outlet={$this->outletA->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Device/Index')
            ->has('devices.data', 1)
            ->where('devices.data.0.device_name', 'Kasir A')
        );
    }

    public function test_it_creates_device_successfully()
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices", [
                'outlet_id' => $this->outletA->id,
                'device_name' => 'Kasir Baru',
                'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
                'serial_number' => 'POS-001',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('outlet_devices', [
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir Baru',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);
    }

    public function test_it_rejects_disabled_device_type()
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices", [
                'outlet_id' => $this->outletA->id,
                'device_name' => 'Kiosk 1',
                'device_type' => DeviceTypeEnum::KIOSK->value,
            ]);

        $response->assertSessionHasErrors('device_type');
    }

    public function test_it_enforces_one_device_limit_without_multi_device_feature()
    {
        // Add 1st device
        OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir 1',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        // Attempt 2nd device on same outlet
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices", [
                'outlet_id' => $this->outletA->id,
                'device_name' => 'Kasir 2',
                'device_type' => DeviceTypeEnum::POS_MOBILE->value,
            ]);

        $response->assertSessionHasErrors('outlet_id');
    }

    public function test_it_allows_multi_device_when_feature_is_active()
    {
        $multiDeviceFeature = Feature::where('code', FeatureEnum::MULTI_DEVICE->value)->first();
        if (! $multiDeviceFeature) {
            $multiDeviceFeature = Feature::create([
                'code' => FeatureEnum::MULTI_DEVICE->value,
                'name' => 'Multi Device',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 340,
            ]);
        }

        $plan = $this->business->getActiveSubscriptionWithPlan()->plan;
        $plan->systemFeatures()->syncWithoutDetaching([$multiDeviceFeature->id]);
        $this->business->clearMemoizedFeatures();
        Cache::flush();

        OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir 1',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices", [
                'outlet_id' => $this->outletA->id,
                'device_name' => 'Kasir 2',
                'device_type' => DeviceTypeEnum::POS_MOBILE->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('outlet_devices', [
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir 2',
        ]);
    }

    public function test_it_generates_pairing_otp()
    {
        $device = OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir OTP',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices/{$device->id}/generate-otp");

        $response->assertRedirect();
        $response->assertSessionHas('otp_data');

        $otpData = session('otp_data');
        $this->assertNotEmpty($otpData['otp']);
        $this->assertEquals($device->id, Cache::get("device_otp_{$otpData['otp']}"));
    }

    public function test_it_unpairs_device()
    {
        $device = OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir Paired',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'uuid-123',
            'hardware_fingerprint' => 'fp-456',
        ]);
        $device->createToken('pos-client');

        $this->assertCount(1, $device->tokens);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/devices/{$device->id}/unpair");

        $response->assertRedirect();
        $device->refresh();

        $this->assertNull($device->client_device_uuid);
        $this->assertNull($device->hardware_fingerprint);
        $this->assertCount(0, $device->tokens);
    }

    public function test_it_deletes_device()
    {
        $device = OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'Kasir Hapus',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/settings/devices/{$device->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('outlet_devices', ['id' => $device->id]);
    }
}
