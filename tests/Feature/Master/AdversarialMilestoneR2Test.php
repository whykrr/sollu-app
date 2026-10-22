<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\VariantGroup;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Services\App\Inventory\RawMaterialService;
use App\Services\App\Master\ProductService;
use App\Services\App\Transaction\MasterDataSyncService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdversarialMilestoneR2Test extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected Uom $uom;

    protected ProductService $productService;

    protected RawMaterialService $rawMaterialService;

    protected MasterDataSyncService $masterDataSyncService;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Adversarial Verification Business',
            'owner_name' => 'Empirical Challenger',
            'email' => 'challenger_'.uniqid().'@test.com',
            'phone' => '081987654321',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Empirical Test Outlet',
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'Pcs'],
            ['name' => 'Pieces', 'category' => 'count']
        );

        $this->productService = app(ProductService::class);
        $this->rawMaterialService = app(RawMaterialService::class);
        $this->masterDataSyncService = app(MasterDataSyncService::class);
    }

    protected function getOutletPayload(): array
    {
        return [
            [
                'outlet_id' => $this->outlet->id,
                'is_enabled' => true,
                'is_available' => true,
            ],
        ];
    }

    // =========================================================================
    // TASK 1: Causality Invariant (sellable = false => is_show = false)
    // =========================================================================

    public function test_causality_direct_model_create_forces_is_show_false(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Unsellable Direct Product',
            'code' => 'ADV-UNSELL-01',
            'product_type' => 'basic',
            'sellable' => false,
            'is_show' => true,
        ]);

        $this->assertFalse((bool) $product->sellable);
        $this->assertFalse((bool) $product->is_show, 'Product saving event must force is_show = false when sellable is false');
        $this->assertFalse((bool) $product->fresh()->is_show);
    }

    public function test_causality_direct_model_update_forces_is_show_false(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Sellable to Unsellable Product',
            'code' => 'ADV-SELL-01',
            'product_type' => 'basic',
            'sellable' => true,
            'is_show' => true,
        ]);

        $product->update([
            'sellable' => false,
            'is_show' => true,
        ]);

        $this->assertFalse((bool) $product->fresh()->sellable);
        $this->assertFalse((bool) $product->fresh()->is_show);
    }

    public function test_causality_direct_product_item_create_forces_is_show_false(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Parent Product',
            'code' => 'ADV-PAR-01',
            'product_type' => 'basic',
            'sellable' => true,
            'is_show' => true,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Unsellable Item',
            'sku' => 'ADV-ITEM-UNSELL-01',
            'sellable' => false,
            'is_show' => true,
            'is_active' => true,
        ]);

        $this->assertFalse((bool) $item->sellable);
        $this->assertFalse((bool) $item->is_show, 'ProductItem saving event must force is_show = false');
        $this->assertFalse((bool) $item->fresh()->is_show);
    }

    public function test_causality_direct_product_item_update_forces_is_show_false(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Parent Product 2',
            'code' => 'ADV-PAR-02',
            'product_type' => 'basic',
            'sellable' => true,
            'is_show' => true,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Item 2',
            'sku' => 'ADV-ITEM-02',
            'sellable' => true,
            'is_show' => true,
            'is_active' => true,
        ]);

        $item->update([
            'sellable' => false,
            'is_show' => true,
        ]);

        $this->assertFalse((bool) $item->fresh()->sellable);
        $this->assertFalse((bool) $item->fresh()->is_show);
    }

    public function test_causality_service_create_single_product_forces_is_show_false(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Service Invariant Single Product',
            'code' => 'ADV-SVC-SGL-01',
            'product_type' => 'basic',
            'base_price' => 50000,
            'track_inventory' => false,
            'has_variant' => false,
            'sellable' => false,
            'is_show' => true,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $this->assertFalse((bool) $product->sellable);
        $this->assertFalse((bool) $product->is_show);

        $defaultItem = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($defaultItem);
        $this->assertFalse((bool) $defaultItem->sellable);
        $this->assertFalse((bool) $defaultItem->is_show);
    }

    public function test_causality_service_variant_matrix_forces_is_show_false_per_item(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Variant Causality Product',
            'code' => 'ADV-VAR-CAUS-01',
            'product_type' => 'basic',
            'base_price' => 25000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Type',
                    'options' => [
                        ['name' => 'Commercial'],
                        ['name' => 'Internal Sample'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Type' => 'Commercial'],
                    'sku' => 'ADV-COMM',
                    'price' => 25000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Type' => 'Internal Sample'],
                    'sku' => 'ADV-SAMPLE',
                    'price' => 0,
                    'sellable' => false,
                    'is_show' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $items = ProductItem::where('product_id', $product->id)->get();
        $commItem = $items->firstWhere('sku', 'ADV-COMM');
        $sampleItem = $items->firstWhere('sku', 'ADV-SAMPLE');

        $this->assertTrue((bool) $commItem->sellable);
        $this->assertTrue((bool) $commItem->is_show);

        $this->assertFalse((bool) $sampleItem->sellable);
        $this->assertFalse((bool) $sampleItem->is_show, 'Variant combination with sellable=false must force is_show=false');
    }

    public function test_causality_raw_material_service_strictly_enforces_unsellable_and_hidden(): void
    {
        $invItem = $this->rawMaterialService->createRawMaterial([
            'name' => 'Bubuk Kakao Premium',
            'sku' => 'RAW-KAKAO-01',
            'uom_id' => $this->uom->id,
            'initial_cost' => 12000,
        ], $this->business);

        $item = $invItem->productItem;
        $this->assertNotNull($item);
        $this->assertFalse((bool) $item->sellable, 'Raw material must always be sellable = false');
        $this->assertFalse((bool) $item->is_show, 'Raw material must always be is_show = false');
        $this->assertTrue((bool) $item->track_inventory, 'Raw material must always have track_inventory = true');
    }

    // =========================================================================
    // TASK 2: Inventory Decoupling (track_inventory = false => 0 rows in inventory_items & inventory_balances)
    // =========================================================================

    public function test_decoupling_create_single_product_untracked_creates_zero_inventory_records(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Decoupled Single Product',
            'code' => 'DEC-SGL-01',
            'product_type' => 'basic',
            'base_price' => 15000,
            'track_inventory' => false,
            'has_variant' => false,
            'sellable' => true,
            'is_show' => true,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($item);
        $this->assertFalse((bool) $product->track_inventory);
        $this->assertFalse((bool) $item->track_inventory);

        // Strict verification: 0 rows in inventory_items
        $invItemsCount = InventoryItem::where('product_item_id', $item->id)->count();
        $this->assertSame(0, $invItemsCount, 'Untracked single product must generate 0 rows in inventory_items');

        // Strict verification: 0 rows in inventory_balances
        $invItemIds = InventoryItem::where('product_item_id', $item->id)->pluck('id');
        $invBalancesCount = InventoryBalance::whereIn('inventory_item_id', $invItemIds)->count();
        $this->assertSame(0, $invBalancesCount, 'Untracked single product must generate 0 rows in inventory_balances');
    }

    public function test_decoupling_create_variant_product_untracked_creates_zero_inventory_records(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Decoupled Variant Product',
            'code' => 'DEC-VAR-01',
            'product_type' => 'basic',
            'base_price' => 30000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [
                        ['name' => 'Small'],
                        ['name' => 'Medium'],
                    ],
                ],
                [
                    'name' => 'Color',
                    'options' => [
                        ['name' => 'Red'],
                        ['name' => 'Blue'],
                    ],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Size' => 'Small', 'Color' => 'Red'], 'sku' => 'DEC-S-R', 'price' => 30000, 'is_active' => true],
                ['options' => ['Size' => 'Small', 'Color' => 'Blue'], 'sku' => 'DEC-S-B', 'price' => 30000, 'is_active' => true],
                ['options' => ['Size' => 'Medium', 'Color' => 'Red'], 'sku' => 'DEC-M-R', 'price' => 32000, 'is_active' => true],
                ['options' => ['Size' => 'Medium', 'Color' => 'Blue'], 'sku' => 'DEC-M-B', 'price' => 32000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $itemIds = ProductItem::where('product_id', $product->id)->pluck('id');
        $this->assertCount(4, $itemIds, 'Must have 4 variant combinations');

        // Assert 0 rows in inventory_items across all items
        $invItems = InventoryItem::whereIn('product_item_id', $itemIds)->get();
        $this->assertCount(0, $invItems, 'Untracked variant matrix must generate 0 rows in inventory_items');

        // Assert 0 rows in inventory_balances
        $invBalancesCount = InventoryBalance::whereIn('inventory_item_id', $invItems->pluck('id'))->count();
        $this->assertSame(0, $invBalancesCount, 'Untracked variant matrix must generate 0 rows in inventory_balances');
    }

    public function test_decoupling_create_service_product_creates_zero_inventory_records(): void
    {
        $service = $this->productService->createService([
            'business_id' => $this->business->id,
            'name' => 'Jasa Konsultasi Kopi',
            'code' => 'DEC-SRV-01',
            'base_price' => 200000,
            'sellable' => true,
            'is_show' => true,
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $service->id)->first();
        $this->assertNotNull($item);
        $this->assertFalse((bool) $service->track_inventory);
        $this->assertFalse((bool) $item->track_inventory);

        $this->assertSame(0, InventoryItem::where('product_item_id', $item->id)->count());
        $this->assertSame(0, InventoryBalance::whereIn('inventory_item_id', InventoryItem::where('product_item_id', $item->id)->select('id'))->count());
    }

    public function test_decoupling_toggle_inventory_tracking_on_update(): void
    {
        // 1. Create untracked product
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Switchable Product',
            'code' => 'SWT-01',
            'product_type' => 'basic',
            'base_price' => 20000,
            'track_inventory' => false,
            'has_variant' => false,
            'sellable' => true,
            'is_show' => true,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $product->id)->first();
        $this->assertSame(0, InventoryItem::where('product_item_id', $item->id)->count());

        // 2. Update to track_inventory = true
        $this->productService->updateProduct($product, [
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $product->refresh();
        $item->refresh();
        $this->assertTrue((bool) $product->track_inventory);
        $this->assertTrue((bool) $item->track_inventory);

        $invItem = InventoryItem::where('product_item_id', $item->id)->first();
        $this->assertNotNull($invItem, 'InventoryItem must be provisioned when switching track_inventory to true');
        $this->assertTrue((bool) $invItem->is_active);

        // 3. Update back to track_inventory = false
        $this->productService->updateProduct($product, [
            'track_inventory' => false,
            'outlets' => $this->getOutletPayload(),
        ]);

        $product->refresh();
        $item->refresh();
        $invItem->refresh();

        $this->assertFalse((bool) $product->track_inventory);
        $this->assertFalse((bool) $item->track_inventory);
        $this->assertFalse((bool) $invItem->is_active, 'InventoryItem must be deactivated when switching track_inventory to false');
    }

    // =========================================================================
    // TASK 3: Pivot Table Sync (product_item_variant_group_options)
    // =========================================================================

    public function test_pivot_sync_two_variant_groups_populates_all_pivot_options(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Kaos Polos Premium',
            'code' => 'TSHIRT-01',
            'product_type' => 'basic',
            'base_price' => 75000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [
                        ['name' => 'S'],
                        ['name' => 'M'],
                    ],
                ],
                [
                    'name' => 'Color',
                    'options' => [
                        ['name' => 'Red'],
                        ['name' => 'Blue'],
                    ],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Size' => 'S', 'Color' => 'Red'], 'sku' => 'TSHIRT-S-RED', 'price' => 75000, 'is_active' => true],
                ['options' => ['Size' => 'S', 'Color' => 'Blue'], 'sku' => 'TSHIRT-S-BLUE', 'price' => 75000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Red'], 'sku' => 'TSHIRT-M-RED', 'price' => 80000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Blue'], 'sku' => 'TSHIRT-M-BLUE', 'price' => 80000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(4, $items);

        $variantGroups = VariantGroup::where('product_id', $product->id)->with('options')->get();
        $sizeGroup = $variantGroups->firstWhere('name', 'Size');
        $colorGroup = $variantGroups->firstWhere('name', 'Color');

        $optS = $sizeGroup->options->firstWhere('name', 'S')->id;
        $optM = $sizeGroup->options->firstWhere('name', 'M')->id;
        $optRed = $colorGroup->options->firstWhere('name', 'Red')->id;
        $optBlue = $colorGroup->options->firstWhere('name', 'Blue')->id;

        // Verify each item's options in pivot table
        $expectedMap = [
            'TSHIRT-S-RED' => [$optS, $optRed],
            'TSHIRT-S-BLUE' => [$optS, $optBlue],
            'TSHIRT-M-RED' => [$optM, $optRed],
            'TSHIRT-M-BLUE' => [$optM, $optBlue],
        ];

        $totalPivotRows = DB::table('product_item_variant_group_options')
            ->whereIn('product_item_id', $items->pluck('id'))
            ->count();

        // 4 combinations * 2 options = 8 pivot rows
        $this->assertSame(8, $totalPivotRows, 'Exact total of 8 pivot entries must exist for 4 combinations across 2 variant groups');

        foreach ($expectedMap as $sku => $expectedOptIds) {
            $item = $items->firstWhere('sku', $sku);
            $this->assertNotNull($item, "Item with SKU {$sku} must exist");

            $linkedOptions = DB::table('product_item_variant_group_options')
                ->where('product_item_id', $item->id)
                ->pluck('variant_group_option_id')
                ->all();

            sort($expectedOptIds);
            sort($linkedOptions);

            $this->assertEquals($expectedOptIds, $linkedOptions, "Pivot options for {$sku} do not match expected option IDs");
            $this->assertCount(2, $linkedOptions, "Item {$sku} must link to exactly 2 variant options");
        }
    }

    public function test_pivot_sync_three_variant_groups_populates_all_pivot_options(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Jaket Custom 3-Dimensi',
            'code' => 'JKT-3D',
            'product_type' => 'basic',
            'base_price' => 150000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [['name' => 'S'], ['name' => 'M']],
                ],
                [
                    'name' => 'Color',
                    'options' => [['name' => 'Black'], ['name' => 'White']],
                ],
                [
                    'name' => 'Material',
                    'options' => [['name' => 'Cotton'], ['name' => 'Fleece']],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Size' => 'S', 'Color' => 'Black', 'Material' => 'Cotton'], 'sku' => 'JKT-S-BL-CT', 'price' => 150000, 'is_active' => true],
                ['options' => ['Size' => 'S', 'Color' => 'Black', 'Material' => 'Fleece'], 'sku' => 'JKT-S-BL-FL', 'price' => 160000, 'is_active' => true],
                ['options' => ['Size' => 'S', 'Color' => 'White', 'Material' => 'Cotton'], 'sku' => 'JKT-S-WH-CT', 'price' => 150000, 'is_active' => true],
                ['options' => ['Size' => 'S', 'Color' => 'White', 'Material' => 'Fleece'], 'sku' => 'JKT-S-WH-FL', 'price' => 160000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Black', 'Material' => 'Cotton'], 'sku' => 'JKT-M-BL-CT', 'price' => 155000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Black', 'Material' => 'Fleece'], 'sku' => 'JKT-M-BL-FL', 'price' => 165000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'White', 'Material' => 'Cotton'], 'sku' => 'JKT-M-WH-CT', 'price' => 155000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'White', 'Material' => 'Fleece'], 'sku' => 'JKT-M-WH-FL', 'price' => 165000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(8, $items, 'Must generate exactly 8 combinations');

        // 8 combinations * 3 options = 24 pivot rows
        $totalPivotRows = DB::table('product_item_variant_group_options')
            ->whereIn('product_item_id', $items->pluck('id'))
            ->count();

        $this->assertSame(24, $totalPivotRows, 'Exact total of 24 pivot entries must exist for 8 combinations across 3 variant groups');

        foreach ($items as $item) {
            $optionsCount = DB::table('product_item_variant_group_options')
                ->where('product_item_id', $item->id)
                ->count();
            $this->assertSame(3, $optionsCount, 'Every variant item must have exactly 3 options linked');
        }
    }

    public function test_pivot_sync_update_matrix_cleans_up_removed_variant_groups_and_pivots(): void
    {
        // 1. Create with 2 groups: Size (S, M) and Color (Red, Blue) = 4 combinations, 8 pivot entries
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Morphing Variant Product',
            'code' => 'MORPH-01',
            'product_type' => 'basic',
            'base_price' => 50000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [['name' => 'S'], ['name' => 'M']],
                ],
                [
                    'name' => 'Color',
                    'options' => [['name' => 'Red'], ['name' => 'Blue']],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Size' => 'S', 'Color' => 'Red'], 'sku' => 'MORPH-S-R', 'price' => 50000, 'is_active' => true],
                ['options' => ['Size' => 'S', 'Color' => 'Blue'], 'sku' => 'MORPH-S-B', 'price' => 50000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Red'], 'sku' => 'MORPH-M-R', 'price' => 55000, 'is_active' => true],
                ['options' => ['Size' => 'M', 'Color' => 'Blue'], 'sku' => 'MORPH-M-B', 'price' => 55000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $initialPivots = DB::table('product_item_variant_group_options')
            ->whereIn('product_item_id', ProductItem::where('product_id', $product->id)->pluck('id'))
            ->count();
        $this->assertSame(8, $initialPivots);

        // 2. Update variant matrix: Remove Color group entirely, retain only Size (S, M)
        $this->productService->updateProduct($product, [
            'has_variant' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [['name' => 'S'], ['name' => 'M']],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Size' => 'S'], 'sku' => 'MORPH-S', 'price' => 50000, 'is_active' => true],
                ['options' => ['Size' => 'M'], 'sku' => 'MORPH-M', 'price' => 55000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $activeItems = ProductItem::where('product_id', $product->id)->where('is_active', true)->get();
        $this->assertCount(2, $activeItems);

        // Check pivot rows for active items: 2 items * 1 option = 2 pivot rows
        $activePivots = DB::table('product_item_variant_group_options')
            ->whereIn('product_item_id', $activeItems->pluck('id'))
            ->count();
        $this->assertSame(2, $activePivots);

        // Verify Color group was deleted and its cascade cleaned up from pivot
        $this->assertDatabaseMissing('variant_groups', [
            'product_id' => $product->id,
            'name' => 'Color',
        ]);
    }

    public function test_pivot_sync_master_data_sync_exports_product_items_and_pivot_options(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Sync Test Drink',
            'code' => 'DRK-01',
            'product_type' => 'basic',
            'base_price' => 22000,
            'track_inventory' => false,
            'has_variant' => true,
            'sellable' => true,
            'is_show' => true,
            'variants' => [
                [
                    'name' => 'Temp',
                    'options' => [['name' => 'Hot'], ['name' => 'Iced']],
                ],
            ],
            'variant_combinations' => [
                ['options' => ['Temp' => 'Hot'], 'sku' => 'DRK-HOT', 'price' => 22000, 'is_active' => true],
                ['options' => ['Temp' => 'Iced'], 'sku' => 'DRK-ICED', 'price' => 24000, 'is_active' => true],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'Adversarial Device',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'dev-uuid-adv-01',
            'hardware_fingerprint' => 'hw-sig-adv-01',
            'is_active' => true,
        ]);

        $syncData = $this->masterDataSyncService->getPayload($device);

        // Assert sync payload structure contains required keys
        $this->assertArrayHasKey('product_items', $syncData);
        $this->assertArrayHasKey('product_item_variant_group_options', $syncData);
        $this->assertArrayHasKey('inventory_item_variant_group_options', $syncData);

        // Assert our items are present in product_items
        $syncedItems = collect($syncData['product_items']);
        $this->assertTrue($syncedItems->contains('sku', 'DRK-HOT'));
        $this->assertTrue($syncedItems->contains('sku', 'DRK-ICED'));

        // Assert pivot options are present
        $syncedPivots = collect($syncData['product_item_variant_group_options']);
        $hotItem = $syncedItems->firstWhere('sku', 'DRK-HOT');
        $icedItem = $syncedItems->firstWhere('sku', 'DRK-ICED');

        $this->assertTrue($syncedPivots->contains('product_item_id', $hotItem['id']));
        $this->assertTrue($syncedPivots->contains('product_item_id', $icedItem['id']));
    }

    // =========================================================================
    // INVARIANT VERIFICATION: ProductService::deleteProduct deactivates InventoryItem
    // =========================================================================

    public function test_delete_product_inventory_item_deactivation_cascade(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Tracked Coffee Product',
            'code' => 'DEL-BUG-01',
            'product_type' => 'basic',
            'base_price' => 25000,
            'track_inventory' => true,
            'has_variant' => false,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($item);

        $invItem = InventoryItem::where('product_item_id', $item->id)->first();
        $this->assertNotNull($invItem);
        $this->assertTrue((bool) $invItem->is_active);

        // Execute deleteProduct
        $this->productService->deleteProduct($product);

        $this->assertTrue($product->fresh()->trashed(), 'Product must be soft deleted');
        $this->assertTrue($item->fresh()->trashed(), 'ProductItem must be soft deleted');

        $this->assertFalse(
            (bool) $invItem->fresh()->is_active,
            'inventory_items.is_active must be updated to false when product is deleted'
        );
    }

    public function test_defect_a_multi_variant_product_deletion_deactivates_all_inventory_items(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Multi Variant Inventory Coffee',
            'code' => 'VAR-DEL-01',
            'product_type' => 'basic',
            'base_price' => 25000,
            'track_inventory' => true,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [
                        ['name' => 'Small'],
                        ['name' => 'Large'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Size' => 'Small'],
                    'sku' => 'VAR-DEL-S',
                    'barcode' => '8999801',
                    'price' => 25000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Size' => 'Large'],
                    'sku' => 'VAR-DEL-L',
                    'barcode' => '8999802',
                    'price' => 30000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(2, $items);

        $invItemIds = [];
        foreach ($items as $it) {
            $invItem = InventoryItem::where('product_item_id', $it->id)->first();
            $this->assertNotNull($invItem, "InventoryItem must exist for {$it->sku}");
            $this->assertTrue((bool) $invItem->is_active);
            $invItemIds[] = $invItem->id;
        }

        // Execute deleteProduct
        $this->productService->deleteProduct($product);

        $this->assertTrue($product->fresh()->trashed(), 'Product must be soft deleted');
        $this->assertEquals(0, ProductItem::where('product_id', $product->id)->count(), 'Active items must be 0');
        $this->assertEquals(2, ProductItem::withTrashed()->where('product_id', $product->id)->count(), 'Trashed items must be 2');

        foreach ($invItemIds as $invId) {
            $inv = InventoryItem::find($invId);
            $this->assertFalse((bool) $inv->is_active, "All variant inventory items must be deactivated, failed on ID: {$invId}");
        }
    }

    public function test_defect_a_decoupled_product_deletion_without_inventory_items_succeeds(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Decoupled Non-Inventory Product',
            'code' => 'DEC-DEL-01',
            'product_type' => 'basic',
            'base_price' => 15000,
            'track_inventory' => false,
            'has_variant' => false,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(0, InventoryItem::where('product_item_id', $item->id)->count());

        // Deleting decoupled product must succeed cleanly
        $result = $this->productService->deleteProduct($product);
        $this->assertTrue($result);
        $this->assertTrue($product->fresh()->trashed());
        $this->assertTrue($item->fresh()->trashed());
    }

    public function test_defect_b_variant_product_update_without_variants_payload_forces_child_items_causality(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Variant Causality Check',
            'code' => 'VCU-001',
            'product_type' => 'basic',
            'base_price' => 20000,
            'track_inventory' => false,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [
                        ['name' => 'Regular'],
                        ['name' => 'Large'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Size' => 'Regular'],
                    'sku' => 'VCU-REG',
                    'price' => 20000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Size' => 'Large'],
                    'sku' => 'VCU-LRG',
                    'price' => 25000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertTrue((bool) $item->sellable);
            $this->assertTrue((bool) $item->is_show);
        }

        // Adversarial update: update parent to sellable = false without sending variants array
        $this->productService->updateProduct($product, [
            'sellable' => false,
            'name' => 'Variant Causality Check (Disabled)',
        ]);

        $this->assertFalse((bool) $product->fresh()->sellable);
        $this->assertFalse((bool) $product->fresh()->is_show);

        foreach ($items as $item) {
            $freshItem = $item->fresh();
            $this->assertFalse(
                (bool) $freshItem->sellable,
                "Variant item {$freshItem->sku} must have sellable = false when parent product sellable is false"
            );
            $this->assertFalse(
                (bool) $freshItem->is_show,
                "Variant item {$freshItem->sku} must have is_show = false when parent product sellable is false"
            );
        }
    }

    public function test_defect_b_variant_product_update_with_contradictory_combinations_payload_forces_unsellable(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Contradictory Variant Product',
            'code' => 'CON-001',
            'product_type' => 'basic',
            'base_price' => 15000,
            'track_inventory' => false,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'variants' => [
                [
                    'name' => 'Type',
                    'options' => [
                        ['name' => 'Hot'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Type' => 'Hot'],
                    'sku' => 'CON-HOT',
                    'price' => 15000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        // Malicious or conflicting payload: parent product sellable = false, but variant combination claims sellable = true and is_show = true
        $this->productService->updateProduct($product, [
            'name' => 'Contradictory Variant Product Updated',
            'sellable' => false,
            'variants' => [
                [
                    'name' => 'Type',
                    'options' => [
                        ['name' => 'Hot'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Type' => 'Hot'],
                    'sku' => 'CON-HOT',
                    'price' => 16000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        $item = ProductItem::where('product_id', $product->id)->where('sku', 'CON-HOT')->first();
        $this->assertNotNull($item);
        $this->assertFalse((bool) $product->fresh()->sellable);
        $this->assertFalse((bool) $product->fresh()->is_show);
        $this->assertFalse((bool) $item->fresh()->sellable, 'Child item sellable must be forced to false');
        $this->assertFalse((bool) $item->fresh()->is_show, 'Child item is_show must be forced to false');
    }

    public function test_defect_b_variant_product_update_with_is_show_false_cascades_to_child_items(): void
    {
        $product = $this->productService->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Hidden Variant Product',
            'code' => 'HID-001',
            'product_type' => 'basic',
            'base_price' => 18000,
            'track_inventory' => false,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'variants' => [
                [
                    'name' => 'Option',
                    'options' => [
                        ['name' => 'A'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Option' => 'A'],
                    'sku' => 'HID-A',
                    'price' => 18000,
                    'sellable' => true,
                    'is_show' => true,
                    'is_active' => true,
                ],
            ],
            'outlets' => $this->getOutletPayload(),
        ]);

        // Update product to is_show = false (while sellable remains true)
        $this->productService->updateProduct($product, [
            'name' => 'Hidden Variant Product Updated',
            'sellable' => true,
            'is_show' => false,
        ]);

        $this->assertTrue((bool) $product->fresh()->sellable);
        $this->assertFalse((bool) $product->fresh()->is_show);

        $item = ProductItem::where('product_id', $product->id)->where('sku', 'HID-A')->first();
        $this->assertNotNull($item);
        $this->assertTrue((bool) $item->fresh()->sellable);
        $this->assertFalse((bool) $item->fresh()->is_show, 'Child item is_show must be forced to false when parent is_show is false');
    }

    public function test_service_product_deletion_cascades_to_child_product_items(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Haircut Treatment',
            'code' => 'SRV-HCT',
            'product_type' => 'service',
            'is_show' => true,
            'sellable' => true,
            'track_inventory' => false,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $service->id,
            'item_type' => 'variant_sku',
            'name' => 'Haircut Treatment',
            'sku' => 'SRV-HCT',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
            'track_inventory' => false,
        ]);

        $result = $this->productService->deleteService($service);
        $this->assertTrue($result);

        $this->assertTrue($service->fresh()->trashed(), 'Service product must be soft-deleted');
        $this->assertTrue($item->fresh()->trashed(), 'Child item of service product must be soft-deleted');
    }
}
