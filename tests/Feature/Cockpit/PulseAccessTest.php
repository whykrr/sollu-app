<?php

namespace Tests\Feature\Cockpit;

use App\Enums\DeviceTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\PulseServiceProvider;
use Tests\TestCase;

class PulseAccessTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $activeAdmin;

    protected CockpitUser $inactiveAdmin;

    protected string $cockpitHost;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pulse.enabled' => true]);
        config(['pulse.domain' => 'cockpit.sollu.test']);
        config(['pulse.storage.database.connection' => 'sqlite']);

        if (! $this->app->providerIsLoaded(PulseServiceProvider::class)) {
            $this->app->register(PulseServiceProvider::class);
            $this->app->register(\App\Providers\PulseServiceProvider::class);
        }

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $this->activeAdmin = CockpitUser::create([
            'name' => 'Active Admin',
            'email' => 'active_admin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->inactiveAdmin = CockpitUser::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive_admin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);
    }

    public function test_unauthenticated_guest_is_redirected(): void
    {
        $response = $this->get("http://{$this->cockpitHost}/pulse");

        $response->assertStatus(302);
    }

    public function test_active_cockpit_user_can_access_pulse(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/pulse");

        $response->assertStatus(200);
    }

    public function test_inactive_cockpit_user_is_forbidden_from_pulse(): void
    {
        $response = $this->actingAs($this->inactiveAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/pulse");

        $response->assertStatus(302);
    }

    public function test_regular_business_user_cannot_access_pulse(): void
    {
        $type = BusinessType::create(['name' => 'F&B', 'code' => 'fnb_iso']);
        $business = Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant@test.com',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $regularUser = User::factory()->create(['business_id' => $business->id]);

        $response = $this->actingAs($regularUser, 'business')
            ->get("http://{$this->cockpitHost}/pulse");

        $response->assertStatus(302);
    }

    public function test_allowed_emails_env_restricts_access(): void
    {
        putenv('PULSE_ALLOWED_EMAILS=superadmin@sollu.test');

        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/pulse");
        $response->assertStatus(302);

        $superAdmin = CockpitUser::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/pulse");
        $response->assertStatus(200);

        putenv('PULSE_ALLOWED_EMAILS');
    }

    public function test_pulse_user_resolver_resolves_business_user_device_and_cockpit_user(): void
    {
        $type = BusinessType::create(['name' => 'F&B', 'code' => 'fnb_iso']);
        $business = Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant@test.com',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Kemang',
        ]);

        $businessUser = User::factory()->create([
            'business_id' => $business->id,
            'name' => 'Tenant User',
            'email' => 'tenant@test.com',
        ]);
        $businessUser->outlets()->attach($outlet->id);

        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Kasir Utama 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL,
            'is_active' => true,
        ]);

        $resolver = app(ResolvesUsers::class);
        $keys = collect([$businessUser->id, $device->id, $this->activeAdmin->id, 'non-existent-uuid']);

        $resolver->load($keys);

        $resolvedBusiness = $resolver->find($businessUser->id);
        $resolvedDevice = $resolver->find($device->id);
        $resolvedCockpit = $resolver->find($this->activeAdmin->id);
        $resolvedMissing = $resolver->find('non-existent-uuid');

        $this->assertSame('Tenant User', $resolvedBusiness->name);
        $this->assertSame('Outlet: Outlet Kemang • tenant@test.com', $resolvedBusiness->extra);

        $this->assertSame('Kasir Utama 01 (Perangkat)', $resolvedDevice->name);
        $this->assertSame('Outlet: Outlet Kemang', $resolvedDevice->extra);

        $this->assertSame('Active Admin (Cockpit)', $resolvedCockpit->name);
        $this->assertSame('active_admin@sollu.test', $resolvedCockpit->extra);

        $this->assertSame('ID: non-existent-uuid', $resolvedMissing->name);
    }
}
