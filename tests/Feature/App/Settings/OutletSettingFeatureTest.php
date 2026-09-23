<?php

namespace Tests\Feature\App\Settings;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletSettingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outletMain;

    protected Outlet $outletBranch;

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
        $this->user->givePermissionTo(PermissionEnum::OUTLET_VIEW->value);
        $this->user->givePermissionTo(PermissionEnum::OUTLET_CREATE->value);
        $this->user->givePermissionTo(PermissionEnum::OUTLET_UPDATE->value);
        $this->user->givePermissionTo(PermissionEnum::OUTLET_DELETE->value);

        $this->outletMain = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama Pusat',
            'address' => 'Jl. Sudirman No. 1',
            'phone' => '0811111111',
            'email' => 'pusat@test.test',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $this->outletBranch = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang Barat',
            'address' => 'Jl. Gatot Subroto No. 2',
            'phone' => '0822222222',
            'email' => 'barat@test.test',
            'is_active' => false,
            'is_main_outlet' => false,
        ]);

        $plan = SubscriptionPlan::where('code', 'basic')->first() ?? SubscriptionPlan::first();
        $this->business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
    }

    public function test_it_displays_outlets_list()
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/outlets");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Outlet/Index')
            ->has('outlets.data', 2)
            ->has('params')
        );
    }

    public function test_it_filters_outlets_by_search()
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/outlets?search=Barat");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Outlet/Index')
            ->has('outlets.data', 1)
            ->where('outlets.data.0.name', 'Outlet Cabang Barat')
        );
    }

    public function test_it_filters_outlets_by_is_active()
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/outlets?is_active=true");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Outlet/Index')
            ->has('outlets.data', 1)
            ->where('outlets.data.0.name', 'Outlet Utama Pusat')
        );

        $responseInactive = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/outlets?is_active=false");

        $responseInactive->assertStatus(200);
        $responseInactive->assertInertia(fn ($page) => $page
            ->component('Settings/Outlet/Index')
            ->has('outlets.data', 1)
            ->where('outlets.data.0.name', 'Outlet Cabang Barat')
        );
    }

    public function test_it_sorts_outlets()
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/outlets?sort=name&direction=asc");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Outlet/Index')
            ->where('outlets.data.0.name', 'Outlet Cabang Barat')
            ->where('outlets.data.1.name', 'Outlet Utama Pusat')
        );
    }

    public function test_it_updates_outlet_successfully()
    {
        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/settings/outlets/{$this->outletBranch->id}", [
                'name' => 'Outlet Cabang Barat Baru',
                'address' => 'Jl. Gatot Subroto No. 99',
                'phone' => '0899999999',
                'email' => 'baratbaru@test.test',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('outlets', [
            'id' => $this->outletBranch->id,
            'name' => 'Outlet Cabang Barat Baru',
            'phone' => '0899999999',
        ]);
    }

    public function test_it_toggles_outlet_status()
    {
        // Enable inactive branch
        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/settings/outlets/{$this->outletBranch->id}/enabled");

        $response->assertRedirect();
        $this->assertTrue($this->outletBranch->fresh()->is_active);

        // Disable branch
        $responseDisable = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/settings/outlets/{$this->outletBranch->id}");

        $responseDisable->assertRedirect();
        $this->assertFalse($this->outletBranch->fresh()->is_active);
    }

    public function test_it_sets_main_outlet()
    {
        // First activate branch
        $this->outletBranch->update(['is_active' => true]);

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/settings/outlets/{$this->outletBranch->id}/set-main");

        $response->assertRedirect();
        $this->assertTrue($this->outletBranch->fresh()->is_main_outlet);
        $this->assertFalse($this->outletMain->fresh()->is_main_outlet);
    }
}
