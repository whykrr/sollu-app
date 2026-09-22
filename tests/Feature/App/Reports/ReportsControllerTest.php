<?php

namespace Tests\Feature\App\Reports;

use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Jobs\Reports\ExportCashierReportJob;
use App\Jobs\Reports\ExportCustomerReportJob;
use App\Jobs\Reports\ExportProductReportJob;
use App\Jobs\Reports\ExportPromotionReportJob;
use App\Jobs\Reports\ExportSalesReportJob;
use App\Jobs\Reports\ExportStockReportJob;
use App\Jobs\Reports\Pdf\ExportCashierReportPdfJob;
use App\Jobs\Reports\Pdf\ExportCustomerReportPdfJob;
use App\Jobs\Reports\Pdf\ExportProductReportPdfJob;
use App\Jobs\Reports\Pdf\ExportPromotionReportPdfJob;
use App\Jobs\Reports\Pdf\ExportSalesReportPdfJob;
use App\Jobs\Reports\Pdf\ExportStockReportPdfJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsControllerTest extends TestCase
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

        $reportFeatures = [
            FeatureEnum::SALES_REPORTS->value,
            FeatureEnum::PRODUCT_REPORTS->value,
            FeatureEnum::STOCK_REPORTS->value,
            FeatureEnum::CASHIER_REPORTS->value,
            FeatureEnum::PROMO_REPORTS->value,
            FeatureEnum::CUSTOMER_REPORTS->value,
        ];

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => $reportFeatures,
            ]
        );

        $this->business = Business::create([
            'name' => 'Reports Test Business',
            'owner_name' => 'Owner Reports',
            'email' => 'reports_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => $reportFeatures,
            ],
        ]);

        $proPlan = \App\Models\SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();

        if ($proPlan) {
            \App\Models\Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => \App\Enums\SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }

        $this->business->clearMemoizedFeatures();

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Report Admin',
            'email' => 'admin_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        setPermissionsTeamId($this->business->id);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_active' => true,
        ]);
    }

    public function test_sales_report_index_and_exports(): void
    {
        Queue::fake();

        // 1. Index
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/sales?period=this_month&outlet={$this->outlet->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Sales/Index')
            ->has('summary')
            ->has('dailySales')
            ->has('paymentMethods')
            ->has('filters')
            ->where('filters.period', 'this_month')
            ->where('filters.outlet', $this->outlet->id)
        );

        // 2. Export CSV
        $csvResponse = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/sales/export-csv", [
                'period' => 'this_month',
                'outlet' => $this->outlet->id,
            ]);

        $csvResponse->assertRedirect();
        $csvResponse->assertSessionHas('success', ResourceMessage::EXPORT_PROCESSING);
        Queue::assertPushed(ExportSalesReportJob::class);

        // 3. Export PDF
        $pdfResponse = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/sales/export-pdf", [
                'period' => 'this_month',
                'outlet' => $this->outlet->id,
            ]);

        $pdfResponse->assertRedirect();
        $pdfResponse->assertSessionHas('success', ResourceMessage::EXPORT_PROCESSING);
        Queue::assertPushed(ExportSalesReportPdfJob::class);
    }

    public function test_products_report_index_and_exports(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/products?period=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Products/Index')
            ->has('summary')
            ->has('products')
            ->has('filters')
        );

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/products/export-csv", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportProductReportJob::class);

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/products/export-pdf", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportProductReportPdfJob::class);
    }

    public function test_stocks_report_index_and_exports(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/stocks?period=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Stocks/Index')
            ->has('summary')
            ->has('stocks')
            ->has('filters')
        );

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/stocks/export-csv", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportStockReportJob::class);

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/stocks/export-pdf", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportStockReportPdfJob::class);
    }

    public function test_cashiers_report_index_and_exports(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/cashiers?period=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Cashiers/Index')
            ->has('summary')
            ->has('shifts')
            ->has('filters')
        );

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/cashiers/export-csv", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportCashierReportJob::class);

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/cashiers/export-pdf", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportCashierReportPdfJob::class);
    }

    public function test_promotions_report_index_and_exports(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/promotions?period=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Promotions/Index')
            ->has('summary')
            ->has('promotions')
            ->has('filters')
        );

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/promotions/export-csv", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportPromotionReportJob::class);

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/promotions/export-pdf", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportPromotionReportPdfJob::class);
    }

    public function test_customers_report_index_and_exports(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/customers?period=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Customers/Index')
            ->has('summary')
            ->has('customers')
            ->has('filters')
        );

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/customers/export-csv", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportCustomerReportJob::class);

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/reports/customers/export-pdf", ['period' => 'this_month'])
            ->assertRedirect();
        Queue::assertPushed(ExportCustomerReportPdfJob::class);
    }

    public function test_cannot_access_reports_for_outlet_of_another_merchant(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Foreign Business',
            'owner_name' => 'Foreign Owner',
            'email' => 'foreign_'.uniqid().'@test.test',
            'phone' => '081234567899',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $foreignOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign Outlet',
            'is_active' => true,
        ]);

        // Attempting to filter reports with foreign outlet must abort 403
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/reports/sales?period=this_month&outlet={$foreignOutlet->id}");

        $response->assertStatus(403);
    }
}
