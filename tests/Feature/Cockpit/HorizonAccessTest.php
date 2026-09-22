<?php

namespace Tests\Feature\Cockpit;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $activeAdmin;

    protected CockpitUser $inactiveAdmin;

    protected string $cockpitHost;

    protected function setUp(): void
    {
        parent::setUp();

        config(['horizon.domain' => 'cockpit.sollu.test']);

        if (! $this->app->providerIsLoaded(\Laravel\Horizon\HorizonServiceProvider::class)) {
            $this->app->register(\Laravel\Horizon\HorizonServiceProvider::class);
            $this->app->register(\App\Providers\HorizonServiceProvider::class);
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

    public function test_unauthenticated_guest_is_forbidden_from_horizon(): void
    {
        $response = $this->get("http://{$this->cockpitHost}/horizon");

        $response->assertStatus(403);
    }

    public function test_active_cockpit_user_can_access_horizon(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/horizon");

        $response->assertStatus(200);
    }

    public function test_inactive_cockpit_user_is_forbidden_from_horizon(): void
    {
        $response = $this->actingAs($this->inactiveAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/horizon");

        $response->assertStatus(403);
    }

    public function test_regular_business_user_cannot_access_horizon(): void
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
            ->get("http://{$this->cockpitHost}/horizon");

        $response->assertStatus(403);
    }

    public function test_allowed_emails_env_restricts_access(): void
    {
        putenv('HORIZON_ALLOWED_EMAILS=superadmin@sollu.test');

        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/horizon");
        $response->assertStatus(403);

        $superAdmin = CockpitUser::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/horizon");
        $response->assertStatus(200);

        putenv('HORIZON_ALLOWED_EMAILS');
    }
}
