<?php

namespace Tests\Feature\App;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Test Pusat',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_overview(): void
    {
        $response = $this->get("http://{$this->appDomain}/");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_merchant_can_view_overview_dashboard_with_all_props(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Overview/Index')
            ->has('totalSales')
            ->has('totalTransactions')
            ->has('averageSales')
            ->has('lowStockCount')
            ->has('salesTrend')
            ->has('categorySalesTrend')
            ->has('paymentMethodSummary')
            ->has('mostSoldProducts')
            ->has('lowStockProduct')
            ->has('productNotSold')
            ->has('filters')
            ->where('filters.period', 'today')
            ->where('filters.period_label', 'Hari Ini')
        );
    }

    public function test_merchant_can_filter_overview_by_period_and_outlet(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/?period=this_month&outlet={$this->outlet->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Overview/Index')
            ->where('filters.period', 'this_month')
            ->where('filters.period_label', 'Bulan Ini')
            ->where('filters.outlet', $this->outlet->id)
        );
    }

    public function test_invalid_filter_period_is_rejected_gracefully(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/?period=invalid_random_preset");

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['period']);
    }
}
