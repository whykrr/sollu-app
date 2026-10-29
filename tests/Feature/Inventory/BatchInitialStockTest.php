<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Inventory\StockFreezeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchInitialStockTest extends TestCase
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
            'name' => 'Inventory Batch Test Biz',
            'owner_name' => 'Owner Batch',
            'email' => 'batch_'.uniqid().'@test.test',
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
            'name' => 'Outlet Inventori',
            'code' => 'OUT-INV',
            'address' => 'Jl. Inventori No. 10',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Inventory Manager',
            'email' => 'mgr_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->uom = Uom::whereRaw('LOWER(code) = ?', ['pcs'])->first()
            ?? Uom::create(['code' => 'PCS', 'name' => 'Pieces', 'status' => 'active']);

        $this->subscribeBusinessToPlan();
    }

    protected function subscribeBusinessToPlan(): void
    {
        setPermissionsTeamId($this->business->id);
        $plan = SubscriptionPlan::firstOrCreate(
            ['code' => PlanEnum::PRO->value],
            ['name' => 'Pro Plan', 'is_active' => true]
        );

        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addDays(30),
        ]);

        $this->user->refresh();
    }

    protected function createTrackedItem(string $name, string $sku): InventoryItem
    {
        $productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'name' => $name,
            'sku' => $sku,
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        return InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $productItem->id,
            'uom_id' => $this->uom->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_search_eligible_items_returns_only_items_with_no_or_initial_only_movements(): void
    {
        // Item 1: Belum ada mutasi
        $item1 = $this->createTrackedItem('Item Baru Tanpa Mutasi', 'SKU-001');

        // Item 2: Hanya ada mutasi InitialStock
        $item2 = $this->createTrackedItem('Item Ada Stok Awal', 'SKU-002');
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
            'current_stock' => 10,
            'average_cost' => 5000,
            'last_cost' => 5000,
            'total_value' => 50000,
        ]);
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
            'movement_type' => InventoryMovementType::InitialStock,
            'qty_change' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'unit_cost' => 5000,
            'total_cost' => 50000,
            'balance_value_after' => 50000,
            'description' => 'Stok Awal',
        ]);

        // Item 3: Sudah ada transaksi operasional penjualan (Sale)
        $item3 = $this->createTrackedItem('Item Terjual', 'SKU-003');
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item3->id,
            'movement_type' => InventoryMovementType::Sale,
            'qty_change' => -2,
            'stock_before' => 5,
            'stock_after' => 3,
            'description' => 'Penjualan POS',
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/inventories/stocks/initial-stock/eligible-items?outlet_id={$this->outlet->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $ids = array_column($data, 'id');

        $this->assertContains($item1->id, $ids);
        $this->assertContains($item2->id, $ids);
        $this->assertNotContains($item3->id, $ids);

        // Periksa flag is_initial_only
        $foundItem2 = collect($data)->firstWhere('id', $item2->id);
        $this->assertTrue($foundItem2['is_initial_only']);
        $this->assertEquals(10, $foundItem2['current_stock']);
        $this->assertEquals(5000, $foundItem2['current_cost']);

        $foundItem1 = collect($data)->firstWhere('id', $item1->id);
        $this->assertFalse($foundItem1['is_initial_only']);
    }

    public function test_store_batch_initial_stock_creates_costing_and_movements_for_multiple_items(): void
    {
        $item1 = $this->createTrackedItem('Sabun Cuci', 'SBN-01');
        $item2 = $this->createTrackedItem('Shampoo Botol', 'SMP-01');

        $payload = [
            'outlet_id' => $this->outlet->id,
            'items' => [
                [
                    'inventory_item_id' => $item1->id,
                    'qty' => 50,
                    'purchase_price' => 7500,
                ],
                [
                    'inventory_item_id' => $item2->id,
                    'qty' => 30,
                    'purchase_price' => 18000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/inventories/stocks/initial-stock/batch", $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Stok awal berhasil disimpan.']);

        // Verifikasi Item 1
        $balance1 = InventoryBalance::where('inventory_item_id', $item1->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($balance1);
        $this->assertEquals(50, (float) $balance1->current_stock);
        $this->assertEquals(7500, (float) $balance1->average_cost);
        $this->assertEquals(375000, (float) $balance1->total_value);

        $layer1 = InventoryCostLayer::where('inventory_item_id', $item1->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($layer1);
        $this->assertEquals($this->business->id, $layer1->business_id);
        $this->assertEquals(50, (float) $layer1->qty_remaining);

        // Verifikasi Item 2
        $balance2 = InventoryBalance::where('inventory_item_id', $item2->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($balance2);
        $this->assertEquals(30, (float) $balance2->current_stock);
        $this->assertEquals(18000, (float) $balance2->average_cost);
        $this->assertEquals(540000, (float) $balance2->total_value);
    }

    public function test_store_batch_initial_stock_updates_existing_initial_stock_within_grace_period(): void
    {
        $item = $this->createTrackedItem('Kecap Manis', 'KCP-01');

        // Initial setup
        $payload1 = [
            'outlet_id' => $this->outlet->id,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'qty' => 20,
                    'purchase_price' => 10000,
                ],
            ],
        ];

        $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/inventories/stocks/initial-stock/batch", $payload1)
            ->assertStatus(200);

        // Koreksi stok awal: dari 20 @ 10.000 menjadi 25 @ 9.500
        $payloadUpdate = [
            'outlet_id' => $this->outlet->id,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'qty' => 25,
                    'purchase_price' => 9500,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/inventories/stocks/initial-stock/batch", $payloadUpdate);

        $response->assertStatus(200);

        $balance = InventoryBalance::where('inventory_item_id', $item->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(25, (float) $balance->current_stock);
        $this->assertEquals(9500, (float) $balance->average_cost);
        $this->assertEquals(237500, (float) $balance->total_value);

        $layer = InventoryCostLayer::where('inventory_item_id', $item->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(25, (float) $layer->qty_remaining);
        $this->assertEquals(9500, (float) $layer->purchase_price);

        $movement = InventoryMovement::where('inventory_item_id', $item->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(25, (float) $movement->qty_change);
        $this->assertEquals(9500, (float) $movement->unit_cost);
        $this->assertEquals(237500, (float) $movement->total_cost);
    }

    public function test_store_batch_initial_stock_rejects_item_with_operational_movements(): void
    {
        $item = $this->createTrackedItem('Beras 5kg', 'BRS-05');

        // Simulasikan mutasi penjualan
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'movement_type' => InventoryMovementType::Sale,
            'qty_change' => -1,
            'stock_before' => 5,
            'stock_after' => 4,
            'description' => 'Penjualan',
        ]);

        $payload = [
            'outlet_id' => $this->outlet->id,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'qty' => 10,
                    'purchase_price' => 60000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/inventories/stocks/initial-stock/batch", $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "Barang 'Beras 5kg' sudah memiliki riwayat mutasi operasional lanjutan sehingga stok awal tidak dapat diubah lagi.",
        ]);
    }

    public function test_store_batch_initial_stock_rejects_when_outlet_is_frozen(): void
    {
        $item = $this->createTrackedItem('Gula Pasir 1kg', 'GLA-01');

        // Bekukan outlet
        app(StockFreezeService::class)->freeze($this->outlet, $this->user);

        $payload = [
            'outlet_id' => $this->outlet->id,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'qty' => 10,
                    'purchase_price' => 15000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/inventories/stocks/initial-stock/batch", $payload);

        $response->assertStatus(403);
    }
}
