<?php

namespace Tests\Unit\Services\App\Reports;

use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\User;
use App\Services\App\Reports\ProductReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductReportService;
    }

    public function test_it_gets_report()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Report Merchant',
            'owner_name' => 'Report Owner',
            'email' => 'report_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Report User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Report',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'business_id' => $user->business_id,
            'name' => 'Food',
        ]);

        $product = Product::create([
            'business_id' => $user->business_id,
            'product_category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'product_type' => 'basic',
        ]);

        $now = Carbon::now();

        $transaction = Transaction::create([
            'outlet_id' => $outlet->id,
            'status' => 'completed',
            'subtotal' => 100000,
            'total' => 100000,
            'transaction_number' => 'TRX-101',
            'created_at' => $now,
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => 'Nasi Goreng',
            'qty' => 2,
            'price' => 50000,
            'subtotal' => 100000,
        ]);

        // Other business transaction to assert isolation
        $otherBusiness = \App\Models\Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
            'is_active' => true,
        ]);

        $otherCategory = ProductCategory::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Category',
        ]);

        $otherProduct = Product::create([
            'business_id' => $otherBusiness->id,
            'product_category_id' => $otherCategory->id,
            'name' => 'Other Product',
            'product_type' => 'basic',
        ]);

        $otherTx = Transaction::create([
            'outlet_id' => $otherOutlet->id,
            'status' => 'completed',
            'subtotal' => 999000,
            'total' => 999000,
            'transaction_number' => 'TRX-OTHER-1',
            'created_at' => $now,
        ]);

        TransactionItem::create([
            'transaction_id' => $otherTx->id,
            'product_id' => $otherProduct->id,
            'product_name' => 'Other Product',
            'qty' => 10,
            'price' => 99900,
            'subtotal' => 999000,
        ]);

        $startDate = $now->copy()->subDay();
        $endDate = $now->copy()->addDay();

        $result = $this->service->getReport($business->id, [$outlet->id], $startDate, $endDate);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('products', $result);

        $summary = $result['summary'];
        $this->assertEquals(1, $summary['total_products']);
        $this->assertEquals(2, $summary['total_qty']);
        $this->assertEquals(100000, $summary['total_sales']);

        $products = $result['products'];
        $this->assertNotEmpty($products->items());
        $firstItem = $products->items()[0];

        $this->assertEquals('Nasi Goreng', $firstItem->product_name);
        $this->assertEquals('Food', $firstItem->category_name);
        $this->assertEquals(2, $firstItem->total_qty);
        $this->assertEquals(100000, $firstItem->total_sales);
    }
}
