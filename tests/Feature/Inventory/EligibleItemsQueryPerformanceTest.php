<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EligibleItemsQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Uom $uom;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Query Perf Biz',
            'owner_name' => 'Owner Perf',
            'email' => 'perf_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Perf',
            'slug' => 'outlet-perf-'.uniqid(),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::firstOrCreate([
            'code' => PlanEnum::PRO->value,
        ], [
            'name' => 'Pro Plan',
            'price' => 200000,
            'max_outlets' => 5,
            'max_users' => 10,
            'features' => array_column(FeatureEnum::cases(), 'value'),
        ]);

        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->user->outlets()->attach($this->outlet->id);

        $this->uom = Uom::whereRaw('LOWER(code) = ?', ['pcs'])->first()
            ?? Uom::create([
                'business_id' => $this->business->id,
                'name' => 'Pieces',
                'code' => 'PCS',
                'status' => 'active',
            ]);
    }

    public function test_search_eligible_items_has_strictly_bounded_queries_without_n_plus_one(): void
    {
        // Buat 40 produk & item inventori
        for ($i = 1; $i <= 40; $i++) {
            $product = Product::create([
                'business_id' => $this->business->id,
                'name' => "Product Perf {$i}",
                'product_type' => 'basic',
                'track_inventory' => true,
                'sellable' => true,
                'is_show' => true,
            ]);

            $productItem = ProductItem::create([
                'business_id' => $this->business->id,
                'product_id' => $product->id,
                'uom_id' => $this->uom->id,
                'item_type' => 'variant_sku',
                'name' => "Product Perf {$i}",
                'sku' => "PRF-{$i}",
                'track_inventory' => true,
                'sellable' => true,
                'is_active' => true,
            ]);

            $invItem = InventoryItem::create([
                'business_id' => $this->business->id,
                'product_item_id' => $productItem->id,
                'name' => "Product Perf {$i}",
                'uom_id' => $this->uom->id,
                'minimum_stock' => 5,
                'is_active' => true,
            ]);

            InventoryBalance::create([
                'business_id' => $this->business->id,
                'outlet_id' => $this->outlet->id,
                'inventory_item_id' => $invItem->id,
                'current_stock' => 10,
                'average_cost' => 5000,
                'minimum_stock' => 5,
            ]);

            $product->prices()->create([
                'outlet_id' => null,
                'product_item_id' => $productItem->id,
                'amount' => 15000 + ($i * 100),
            ]);

            // Untuk separuh item, buat mutasi InitialStock
            if ($i % 2 === 0) {
                InventoryMovement::create([
                    'business_id' => $this->business->id,
                    'outlet_id' => $this->outlet->id,
                    'inventory_item_id' => $invItem->id,
                    'movement_type' => InventoryMovementType::InitialStock,
                    'qty_change' => 10,
                    'stock_before' => 0,
                    'stock_after' => 10,
                    'cost' => 5000,
                    'unit_cost' => 5000,
                    'total_cost' => 50000,
                    'balance_value_after' => 50000,
                ]);
            }
        }

        // Catat query yang dijalankan selama request
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/inventories/stocks/initial-stock/eligible-items?outlet_id={$this->outlet->id}");

        $response->assertOk();
        $data = $response->json();

        // 40 item harus dikembalikan
        $this->assertCount(40, $data);

        // Verifikasi struktur data mengembalikan detail harga jual dan satuan UOM code
        $first = $data[0];
        $this->assertArrayHasKey('selling_price', $first);
        $this->assertArrayHasKey('uom_code', $first);
        $this->assertArrayHasKey('sku', $first);
        $this->assertGreaterThan(0, $first['selling_price']);
        $this->assertEqualsIgnoringCase('PCS', $first['uom_code']);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        // Verifikasi bahwa untuk 40 item, query controller tetap konstan O(1) tanpa N+1
        $this->assertLessThanOrEqual(
            18,
            count($queries),
            'Query count exceeds O(1) limit! Possible N+1 query detected: '.count($queries).' queries executed.'
        );
    }
}
