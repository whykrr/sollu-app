<?php

namespace Tests\Feature\Cockpit;

use App\Constants\FlashDataVariable;
use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Feature;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $admin;

    protected string $cockpitHost;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
        ]]);
        $this->seed(DatabaseSeeder::class);

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $this->admin = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin_merchant_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_cockpit_merchants(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business");

        $response->assertStatus(302);
        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_admin_can_view_merchants_index_with_metrics_and_filters(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Sollu Mart Fresh',
            'owner_name' => 'John Doe',
            'email' => 'john@sollumart.test',
            'phone' => '081234567890',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Utama Sudirman',
            'address' => 'Jl. Sudirman No. 10',
            'phone' => '081234567890',
            'email' => 'sudirman@sollumart.test',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Business/Index')
            ->has('businesses.data', 1)
            ->has('metrics')
            ->has('businessTypes')
            ->has('filters')
            ->where('metrics.total_merchants', 1)
            ->where('metrics.active_merchants', 1)
            ->where('metrics.total_outlets', 1)
        );
    }

    public function test_admin_can_filter_merchants_by_search_and_status(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $b1 = Business::create([
            'name' => 'Warung Kopi Nusantara',
            'owner_name' => 'Agus Wijaya',
            'email' => 'agus@nusantara.test',
            'phone' => '081111111111',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(7),
            'business_type_id' => $type->id,
        ]);

        $b2 = Business::create([
            'name' => 'Toko Buku Cerdas',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@cerdas.test',
            'phone' => '082222222222',
            'status' => BusinessStatus::Suspended,
            'trial_end_at' => now()->subDays(5),
            'business_type_id' => $type->id,
        ]);

        // Search test
        $responseSearch = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business?search=Nusantara");

        $responseSearch->assertStatus(200);
        $responseSearch->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Business/Index')
            ->has('businesses.data', 1)
            ->where('businesses.data.0.name', 'Warung Kopi Nusantara')
        );

        // Status filter test
        $responseStatus = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business?status=suspended");

        $responseStatus->assertStatus(200);
        $responseStatus->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Business/Index')
            ->has('businesses.data', 1)
            ->where('businesses.data.0.name', 'Toko Buku Cerdas')
        );
    }

    public function test_admin_can_view_merchant_detail_json_with_actual_plan_and_outlets(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Resto Seafood Bahari',
            'owner_name' => 'Hendra Setiawan',
            'email' => 'hendra@bahari.test',
            'phone' => '081333444555',
            'address' => 'Jl. Pantai Indah No. 8',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        // Add 2 Outlets
        $mainOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Bahari Pantai',
            'address' => 'Jl. Pantai Indah No. 8',
            'phone' => '081333444555',
            'email' => 'pantai@bahari.test',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $secondOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Bahari Kota',
            'address' => 'Jl. Sudirman No. 20',
            'phone' => '081333444666',
            'email' => 'kota@bahari.test',
            'is_active' => true,
            'is_main_outlet' => false,
        ]);

        // Add User
        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Hendra Setiawan',
            'email' => 'hendra@bahari.test',
            'password' => bcrypt('secret123'),
            'is_root_user' => true,
        ]);

        // Create a Plan and Subscribe
        $plan = SubscriptionPlan::create([
            'code' => 'pro_merchant_test',
            'name' => 'Paket Pro Enterprise',
            'price_per_outlet' => 150000.00,
            'yearly_discount_percent' => 20,
            'is_active' => true,
            'is_public' => true,
        ]);

        $feature = Feature::first();
        if ($feature) {
            $plan->systemFeatures()->attach($feature->id);
        }

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'yearly',
            'started_at' => now()->subMonth(),
            'expired_at' => now()->addMonths(11),
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business/{$business->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $business->id,
            'name' => 'Resto Seafood Bahari',
            'owner_name' => 'Hendra Setiawan',
            'outlets_count' => 2,
            'users_count' => 1,
            'active_plan' => [
                'type' => 'paid',
                'plan_name' => 'Paket Pro Enterprise',
                'plan_code' => 'pro_merchant_test',
                'billing_cycle' => 'yearly',
                'price_per_outlet' => 150000.0,
                'status' => 'active',
            ],
        ]);

        $response->assertJsonStructure([
            'outlets' => [
                '*' => ['id', 'name', 'address', 'phone', 'email', 'is_active', 'is_main_outlet', 'timezone', 'created_at'],
            ],
            'users' => [
                '*' => ['id', 'name', 'email', 'is_root_user', 'roles'],
            ],
            'subscription_history',
        ]);
    }

    public function test_admin_can_view_trial_merchant_detail_json(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Kopi Trial Baru',
            'owner_name' => 'Rian Pratama',
            'email' => 'rian@trial.test',
            'phone' => '081999888777',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(10),
            'business_type_id' => $type->id,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business/{$business->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $business->id,
            'active_plan' => [
                'type' => 'trial',
                'plan_name' => 'Masa Uji Coba (Trial)',
                'status' => 'trial',
            ],
        ]);
    }

    public function test_admin_can_toggle_merchant_status(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Laundry Bersih Kilat',
            'owner_name' => 'Siti Rahma',
            'email' => 'siti@bersih.test',
            'phone' => '081555666777',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/business/{$business->id}/toggle-status", [
                'status' => 'suspended',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $business->refresh();
        $this->assertEquals(BusinessStatus::Suspended, $business->status);

        $this->assertDatabaseHas('business_status_logs', [
            'business_id' => $business->id,
            'old_status' => 'active',
            'new_status' => 'suspended',
            'changed_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_generate_impersonation_url(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Apotek Sehat Sentosa',
            'owner_name' => 'dr. Maya',
            'email' => 'maya@sehat.test',
            'phone' => '081666777888',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'dr. Maya',
            'email' => 'maya@sehat.test',
            'password' => bcrypt('secret123'),
            'is_root_user' => true,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business/{$business->id}/impersonate/{$user->id}");

        $response->assertStatus(302);
        $this->assertStringContainsString('/impersonate/', $response->headers->get('Location'));
    }
}
