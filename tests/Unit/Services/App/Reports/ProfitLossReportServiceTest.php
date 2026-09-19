<?php

namespace Tests\Unit\Services\App\Reports;

use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\User;
use App\Services\App\Reports\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfitLossReportService $service;

    protected Business $business;

    protected User $user;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProfitLossReportService;

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);
    }

    public function test_it_calculates_profit_and_loss_report_accurately()
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman Kopi',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'product_category_id' => $category->id,
            'product_type' => 'basic',
            'name' => 'Kopi Latte',
            'sku' => 'LATTE-001',
            'price' => 25000,
        ]);

        $invItem = InventoryItem::firstOrCreate([
            'business_id' => $this->business->id,
        ], [
            'name' => 'Biji Kopi Espresso',
            'sku' => 'ESPR-001',
            'item_type' => 'raw_material',
        ]);

        $now = Carbon::now();

        // 1. Buat Transaksi Penjualan Selesai
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'TRX-PL-001',
            'subtotal' => 50000,
            'discount_amount' => 5000,
            'tax_amount' => 4500,
            'service_charge_amount' => 0,
            'total' => 49500,
            'status' => TransactionStatus::Completed,
            'payment_status' => TransactionPaymentStatus::Paid,
            'created_at' => $now,
        ]);

        // Transaction Item (2 cup @ 25k, COGS = 10k per cup -> cogs_amount = 20k)
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'inventory_item_id' => $invItem->id,
            'product_name' => 'Kopi Latte',
            'price' => 25000,
            'qty' => 2,
            'discount_amount' => 5000,
            'subtotal' => 45000,
            'unit_cogs' => 10000,
            'cogs_amount' => 20000,
        ]);

        // 2. Buat Beban Persediaan (Waste & Opname Deficit)
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'movement_type' => 'adjustment_out',
            'qty_change' => -1,
            'stock_before' => 10,
            'stock_after' => 9,
            'unit_cost' => 10000,
            'total_cost' => 10000,
            'created_at' => $now,
        ]);

        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'movement_type' => 'opname_deficit',
            'qty_change' => -0.5,
            'stock_before' => 9,
            'stock_after' => 8.5,
            'unit_cost' => 10000,
            'total_cost' => 5000,
            'created_at' => $now,
        ]);

        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();

        $report = $this->service->getReport($this->outlet->id, $startDate, $endDate);

        $summary = $report['summary'];

        // Assert Revenue
        $this->assertEquals(50000.0, $summary['gross_sales']);
        $this->assertEquals(5000.0, $summary['total_discounts']);
        $this->assertEquals(49500.0, $summary['net_sales']);

        // Assert COGS & Gross Profit
        $this->assertEquals(20000.0, $summary['total_cogs']);
        $this->assertEquals(29500.0, $summary['gross_profit']); // 49.500 - 20.000 = 29.500

        // Assert Inventory Losses & Operating Profit
        $this->assertEquals(10000.0, $summary['waste_cost']);
        $this->assertEquals(5000.0, $summary['opname_deficit_cost']);
        $this->assertEquals(15000.0, $summary['net_inventory_loss']);
        $this->assertEquals(14500.0, $summary['operating_profit']); // 29.500 - 15.000 = 14.500

        // Assert Category Breakdown
        $this->assertNotEmpty($report['category_breakdown']);
        $this->assertEquals('Minuman Kopi', $report['category_breakdown'][0]['category_name']);
        $this->assertEquals(20000.0, $report['category_breakdown'][0]['total_cogs']);
    }
}
