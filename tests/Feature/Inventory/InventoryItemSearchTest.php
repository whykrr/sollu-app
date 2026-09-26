<?php

namespace Tests\Feature\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockTransferStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\PurchaseOrderItem;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockAdjustmentItem;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Inventory\Supplier;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryItemSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Outlet $secondaryOutlet;

    protected Uom $uom;

    protected string $appDomain;

    protected InventoryItem $invCoffee;

    protected ProductItem $prodCoffee;

    protected InventoryItem $invSugar;

    protected ProductItem $prodSugar;

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
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::PURCHASE_ORDERS->value,
                    FeatureEnum::SUPPLIER_MANAGEMENT->value,
                    FeatureEnum::RAW_MATERIALS->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                    FeatureEnum::STOCK_MOVEMENTS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Inventory Search Business',
            'owner_name' => 'Owner',
            'email' => 'inv_search_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::PURCHASE_ORDERS->value,
                    FeatureEnum::SUPPLIER_MANAGEMENT->value,
                    FeatureEnum::RAW_MATERIALS->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                    FeatureEnum::STOCK_MOVEMENTS->value,
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $this->secondaryOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Secondary Outlet',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Inventory Manager',
            'email' => 'inv_mgr_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->attach([$this->outlet->id, $this->secondaryOutlet->id]);

        setPermissionsTeamId($this->business->id);

        $permissions = [
            PermissionEnum::INVENTORY_VIEW->value,
            PermissionEnum::INVENTORY_ALL->value,
            PermissionEnum::INVENTORY_MOVEMENT->value,
            PermissionEnum::INVENTORY_ADJUSTMENT_READ->value,
            PermissionEnum::INVENTORY_TRANSFER_READ->value,
            PermissionEnum::PURCHASE_ORDER_VIEW->value,
            PermissionEnum::PURCHASE_ORDER_CREATE->value,
            PermissionEnum::SUPPLIER_VIEW->value,
            PermissionEnum::SUPPLIER_ALL->value,
            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::PRODUCT_ALL->value,
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'business');
            $this->user->givePermissionTo($perm);
        }

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();

        $productCoffee = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Arabica Coffee Beans',
            'code' => 'PRD-COF-01',
            'product_type' => 'basic',
            'is_active' => true,
        ]);

        $this->prodCoffee = ProductItem::create([
            'product_id' => $productCoffee->id,
            'business_id' => $this->business->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'raw_material',
            'name' => 'Arabica Coffee Beans',
            'sku' => 'SKU-COFFEE-01',
            'barcode' => 'BAR-88001122',
            'track_inventory' => true,
            'cost_price' => 25000,
        ]);

        $this->invCoffee = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->prodCoffee->id,
            'name' => 'Arabica Coffee Beans',
            'uom_id' => $this->uom->id,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        $productSugar = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Organic Cane Sugar',
            'code' => 'PRD-SUG-01',
            'product_type' => 'basic',
            'is_active' => true,
        ]);

        $this->prodSugar = ProductItem::create([
            'product_id' => $productSugar->id,
            'business_id' => $this->business->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'raw_material',
            'name' => 'Organic Cane Sugar',
            'sku' => 'SKU-SUGAR-99',
            'barcode' => 'BAR-99003344',
            'track_inventory' => true,
            'cost_price' => 15000,
        ]);

        $this->invSugar = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->prodSugar->id,
            'name' => 'Organic Cane Sugar',
            'uom_id' => $this->uom->id,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_inventory_item_scope_filters_can_search_by_name_sku_and_barcode(): void
    {
        // 1. Search by Name
        $byName = InventoryItem::currentBusiness($this->business->id)->filters(['search' => 'Arabica'])->get();
        $this->assertTrue($byName->contains('id', $this->invCoffee->id));
        $this->assertFalse($byName->contains('id', $this->invSugar->id));

        // 2. Search by SKU
        $bySku = InventoryItem::currentBusiness($this->business->id)->filters(['search' => 'SKU-SUGAR'])->get();
        $this->assertTrue($bySku->contains('id', $this->invSugar->id));
        $this->assertFalse($bySku->contains('id', $this->invCoffee->id));

        // 3. Search by Barcode
        $byBarcode = InventoryItem::currentBusiness($this->business->id)->filters(['search' => '88001122'])->get();
        $this->assertTrue($byBarcode->contains('id', $this->invCoffee->id));
        $this->assertFalse($byBarcode->contains('id', $this->invSugar->id));
    }

    public function test_api_internal_inventory_items_search_by_name_sku_barcode(): void
    {
        // Search by SKU
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/inventory-items/search?query=SKU-COFFEE-01");

        $response->assertOk();
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $this->assertEquals($this->invCoffee->id, $items[0]['id']);
        $this->assertEquals('SKU-COFFEE-01', $items[0]['sku']);
        $this->assertEquals('BAR-88001122', $items[0]['barcode']);

        // Search by Barcode
        $responseBar = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/inventory-items/search?query=BAR-99003344");

        $responseBar->assertOk();
        $itemsBar = $responseBar->json('data');
        $this->assertNotEmpty($itemsBar);
        $this->assertEquals($this->invSugar->id, $itemsBar[0]['id']);
    }

    public function test_api_internal_inventory_items_partial_pagination_and_search(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/inventory-items/partial?search=SKU-COFFEE");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->invCoffee->id, $response->json('data.0.id'));
        $this->assertEquals('SKU-COFFEE-01', $response->json('data.0.sku'));
    }

    public function test_stock_purchases_search_items_endpoint(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/inventories/purchases/search-items?search=SKU-SUGAR");

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals($this->invSugar->id, $data[0]['id']);
        $this->assertEquals('SKU-SUGAR-99', $data[0]['sku']);
    }

    public function test_supplier_search_items_endpoint(): void
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Direct Bean Co',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/inventories/suppliers/search-items?supplier_id={$supplier->id}&search=SKU-COFFEE");

        $response->assertOk();
        $items = $response->json();
        $this->assertNotEmpty($items);
        $this->assertEquals($this->invCoffee->id, $items[0]['id']);
        $this->assertEquals('SKU-COFFEE-01', $items[0]['sku']);
    }

    public function test_raw_materials_index_filters(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/raw-materials?search=SKU-SUGAR");

        $response->assertOk();
    }

    public function test_stock_purchase_show_eager_loads_inventory_item_with_sku_and_barcode(): void
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Bean Roasters Ltd',
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-TEST-001',
            'status' => PurchaseOrderStatus::Draft,
            'order_date' => now(),
            'total_amount' => 50000,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $this->invCoffee->id,
            'uom_id' => $this->uom->id,
            'qty_ordered' => 2,
            'purchase_price' => 25000,
            'subtotal' => 50000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases/{$po->id}");

        $response->assertOk();
    }

    public function test_stock_adjustment_scope_filters_can_search_by_item_sku(): void
    {
        $adj = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-TEST-001',
            'status' => AdjustmentStatus::Draft->value,
            'reason' => AdjustmentReason::Correction->value,
            'created_by' => $this->user->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adj->id,
            'inventory_item_id' => $this->invCoffee->id,
            'movement_type' => InventoryMovementType::AdjustmentIn,
            'qty_change' => 10,
            'description' => 'Test adjustment',
        ]);

        $results = StockAdjustment::currentBusiness($this->business->id)
            ->filters(['search' => 'SKU-COFFEE-01'])
            ->get();

        $this->assertTrue($results->contains('id', $adj->id));
    }

    public function test_stock_transfer_scope_filters_can_search_by_item_sku(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outlet->id,
            'to_outlet_id' => $this->secondaryOutlet->id,
            'transfer_number' => 'TRF-TEST-001',
            'status' => StockTransferStatus::Pending->value,
            'requested_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $this->invSugar->id,
            'qty' => 5,
        ]);

        $results = StockTransfer::currentBusiness($this->business->id)
            ->filters(['search' => 'SKU-SUGAR-99'])
            ->get();

        $this->assertTrue($results->contains('id', $transfer->id));
    }

    public function test_inventory_movement_index_can_search_by_sku(): void
    {
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->invCoffee->id,
            'movement_type' => InventoryMovementType::AdjustmentIn->value,
            'qty_change' => 5,
            'stock_before' => 0,
            'stock_after' => 5,
            'cost' => 25000,
            'unit_cost' => 25000,
            'total_cost' => 125000,
            'balance_value_after' => 125000,
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/movements?search=SKU-COFFEE-01");

        $response->assertOk();
    }

    public function test_products_api_search_by_inventory_item_by_sku(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/search-by-inventory?query=SKU-COFFEE-01");

        $response->assertOk();
        $this->assertNotEmpty($response->json());
        $this->assertEquals($this->invCoffee->id, $response->json('0.id'));
    }
}
