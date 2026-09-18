<?php

namespace Tests\Feature\Cockpit;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Outlet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
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
            'email' => 'admin_dashboard_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_cockpit_dashboard(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/");

        $response->assertStatus(302);
        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_admin_can_view_cockpit_dashboard_with_all_props(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Minimarket & Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Sollu Mart Sudirman',
            'owner_name' => 'Budi',
            'email' => 'budi@sudirman.test',
            'phone' => '081234567890',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Sudirman',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Dashboard/Index')
            ->has('metrics')
            ->has('revenueTrend')
            ->has('acquisitionTrend')
            ->has('planDistribution')
            ->has('businessTypeDistribution')
            ->has('pendingInvoices')
            ->has('recentMerchants')
            ->has('filters')
            ->where('filters.period', 'this_month')
            ->where('metrics.total_merchants', 1)
            ->where('metrics.active_merchants', 1)
            ->where('metrics.total_outlets', 1)
        );
    }

    public function test_admin_can_filter_dashboard_by_period(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/?period=last_30_days");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Dashboard/Index')
            ->where('filters.period', 'last_30_days')
            ->where('filters.period_label', '30 Hari Terakhir')
        );
    }
}
