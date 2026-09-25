<?php

namespace Tests\Unit\Services\App\Reports;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\Sales\TransactionPayment;
use App\Services\App\Reports\DashboardService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService;
    }

    protected function createMerchant(string $name): Business
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        return Business::create([
            'name' => $name,
            'owner_name' => 'Owner '.$name,
            'email' => 'biz_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
    }

    public function test_it_returns_dashboard_metrics_and_trends_with_tenant_isolation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $businessA = $this->createMerchant('Business Utama');
        $businessId = $businessA->id;

        // Create secondary business to test strict tenant isolation
        $otherBusiness = $this->createMerchant('Competitor Store');

        $outletA = Outlet::create([
            'business_id' => $businessId,
            'name' => 'Outlet Utama',
        ]);

        $outletOther = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Outlet Competitor',
        ]);

        $category = ProductCategory::create([
            'business_id' => $businessId,
            'name' => 'Makanan',
        ]);

        $productA = Product::create([
            'business_id' => $businessId,
            'product_category_id' => $category->id,
            'name' => 'Nasi Goreng Spesial',
            'product_type' => 'basic',
        ]);

        $paymentMethod = PaymentMethod::create([
            'business_id' => $businessId,
            'name' => 'QRIS',
            'type' => 'digital',
        ]);

        $now = Carbon::now();

        // Transaction for Business A
        $txA = Transaction::create([
            'outlet_id' => $outletA->id,
            'status' => 'completed',
            'subtotal' => 150000,
            'total' => 150000,
            'transaction_number' => 'TRX-001',
            'created_at' => $now,
        ]);

        TransactionItem::create([
            'transaction_id' => $txA->id,
            'product_id' => $productA->id,
            'product_name' => 'Nasi Goreng Spesial',
            'qty' => 3,
            'price' => 50000,
            'subtotal' => 150000,
        ]);

        TransactionPayment::create([
            'transaction_id' => $txA->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 150000,
        ]);

        // Transaction for Other Business (MUST NOT LEAK)
        $txOther = Transaction::create([
            'outlet_id' => $outletOther->id,
            'status' => 'completed',
            'subtotal' => 999000,
            'total' => 999000,
            'transaction_number' => 'TRX-OTHER-999',
            'created_at' => $now,
        ]);

        // Inventory item below minimum stock for Business A
        $productItem = ProductItem::create([
            'business_id' => $businessId,
            'name' => 'Beras Premium',
            'item_type' => 'raw_material',
            'track_inventory' => true,
        ]);

        $invItem = InventoryItem::create([
            'business_id' => $businessId,
            'product_item_id' => $productItem->id,
            'minimum_stock' => 10,
        ]);

        DB::table('inventory_balances')->insert([
            'id' => Str::uuid()->toString(),
            'business_id' => $businessId,
            'outlet_id' => $outletA->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
        $prevStartDate = $now->copy()->subDay()->startOfDay();
        $prevEndDate = $now->copy()->subDay()->endOfDay();

        // 1. Test Metrics & Multi-Tenant Isolation (Semua Outlet)
        $metrics = $this->service->getMetrics($businessId, [], $startDate, $endDate, $prevStartDate, $prevEndDate);
        $this->assertEquals(150000.0, $metrics['totalSales']['now']);
        $this->assertEquals(1, $metrics['totalTransactions']['now']);
        $this->assertEquals(150000.0, $metrics['averageSales']['now']);
        $this->assertEquals(1, $metrics['lowStockCount']);

        // 2. Test Sales Trend (Today)
        $trend = $this->service->getSalesTrend($businessId, [], $startDate, $endDate, $prevStartDate, $prevEndDate, true);
        $this->assertCount(24, $trend['label']);
        $this->assertCount(2, $trend['value']);
        $this->assertEquals('Periode Ini', $trend['value'][0]['title']);

        // 3. Test Category Sales Trend
        $categoryTrend = $this->service->getCategorySalesTrend($businessId, [], $startDate, $endDate);
        $this->assertContains('Makanan', $categoryTrend['label']);
        $this->assertContains(150000.0, $categoryTrend['value']);

        // 4. Test Payment Method Summary
        $paymentSummary = $this->service->getPaymentMethodSummary($businessId, [], $startDate, $endDate);
        $this->assertContains('QRIS', $paymentSummary['label']);
        $this->assertContains(100, $paymentSummary['value']);

        // 5. Test Most Sold Products
        $mostSold = $this->service->getMostSoldProducts($businessId, [], $startDate, $endDate);
        $this->assertCount(1, $mostSold);
        $this->assertEquals('Nasi Goreng Spesial', $mostSold[0]['name']);
        $this->assertEquals(3, $mostSold[0]['total']);
        $this->assertEquals(150000.0, $mostSold[0]['revenue']);

        // 6. Test Low Stock Products
        $lowStock = $this->service->getLowStockProducts($businessId, []);
        $this->assertCount(1, $lowStock);
        $this->assertEquals('Beras Premium', $lowStock[0]['name']);
        $this->assertEquals(4, $lowStock[0]['stock']);
        $this->assertEquals(10, $lowStock[0]['min_stock']);

        // 7. Test Product Not Sold
        $unsoldProduct = Product::create([
            'business_id' => $businessId,
            'name' => 'Es Teh Manis',
            'product_type' => 'basic',
        ]);
        $notSold = $this->service->getProductNotSold($businessId, [], $startDate, $endDate);
        $this->assertCount(1, $notSold);
        $this->assertEquals('Es Teh Manis', $notSold[0]['name']);

        // 8. Full getDashboardData Caching Test
        $data = $this->service->getDashboardData($businessId, ['period' => 'today']);
        $this->assertArrayHasKey('totalSales', $data);
        $this->assertArrayHasKey('salesTrend', $data);
        $this->assertArrayHasKey('filters', $data);
        $this->assertEquals('Hari Ini', $data['filters']['period_label']);
    }
}
