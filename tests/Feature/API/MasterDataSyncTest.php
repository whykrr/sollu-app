<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductPrice;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected OutletDevice $deviceA;

    protected Uom $uom;

    protected Product $singleProduct;

    protected ProductItem $singleProductItem;

    protected Product $variantProduct;

    protected ProductItem $varItem1;

    protected ProductItem $varItem2;

    protected VariantGroupOption $opt1;

    protected VariantGroupOption $opt2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Sync Merchant POS',
            'owner_name' => 'Merchant Owner',
            'email' => 'sync_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
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

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Kemang',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Senopati',
            'is_active' => true,
        ]);

        $this->deviceA = OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'POS Kemang 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'dev-uuid-kemang-01',
            'hardware_fingerprint' => 'hw-sig-kemang-01',
            'is_active' => true,
        ]);
        app(PosDeviceAuthCacheService::class)->putDevice($this->deviceA);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'Cup'],
            ['name' => 'Cup', 'category' => 'count']
        );

        // 1. Single Product attached to Outlet A
        $this->singleProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Americano Iced',
            'code' => 'AME-001',
            'product_type' => 'basic',
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
        ]);
        $this->singleProduct->outlets()->attach($this->outletA->id, ['is_enabled' => true, 'is_available' => true]);

        $this->singleProductItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->singleProduct->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'variant_sku',
            'name' => 'Americano Iced',
            'sku' => 'AME-001',
            'barcode' => '899000111',
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $this->singleProduct->id,
            'product_item_id' => $this->singleProductItem->id,
            'amount' => 18000,
        ]);

        // 2. Variant Product attached to Outlet A (with Inventory tracking)
        $this->variantProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Matcha Latte',
            'code' => 'MTC-001',
            'product_type' => 'basic',
            'has_variant' => true,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $this->variantProduct->outlets()->attach($this->outletA->id, ['is_enabled' => true, 'is_available' => true]);

        $group = VariantGroup::create([
            'product_id' => $this->variantProduct->id,
            'name' => 'Suhu',
        ]);
        $this->opt1 = VariantGroupOption::create([
            'variant_group_id' => $group->id,
            'name' => 'Hot',
        ]);
        $this->opt2 = VariantGroupOption::create([
            'variant_group_id' => $group->id,
            'name' => 'Cold',
        ]);

        $this->varItem1 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->variantProduct->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'variant_sku',
            'name' => 'Matcha Latte - Hot',
            'variant_combination' => 'Hot',
            'sku' => 'MTC-HOT',
            'barcode' => '899000221',
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);
        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $this->varItem1->id,
            'variant_group_option_id' => $this->opt1->id,
        ]);

        $inv1 = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->varItem1->id,
            'name' => 'Matcha Latte - Hot',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inv1->id,
            'current_stock' => 15,
        ]);

        $this->varItem2 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->variantProduct->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'variant_sku',
            'name' => 'Matcha Latte - Cold',
            'variant_combination' => 'Cold',
            'sku' => 'MTC-CLD',
            'barcode' => '899000222',
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);
        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $this->varItem2->id,
            'variant_group_option_id' => $this->opt2->id,
        ]);

        $inv2 = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->varItem2->id,
            'name' => 'Matcha Latte - Cold',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inv2->id,
            'current_stock' => 20,
        ]);

        // 3. Product attached ONLY to Outlet B (must NOT be exported to Device A)
        $productB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Exclusive Senopati Bread',
            'code' => 'EXC-B',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);
        $productB->outlets()->attach($this->outletB->id, ['is_enabled' => true, 'is_available' => true]);

        ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $productB->id,
            'item_type' => 'variant_sku',
            'name' => 'Exclusive Senopati Bread',
            'sku' => 'EXC-B-01',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);
    }

    public function test_sync_payload_contains_product_items_and_variant_pivot(): void
    {
        Sanctum::actingAs($this->deviceA, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-kemang-01',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-kemang-01',
        ])->getJson('http://api.sollu.test/pos/sync/master?force=1');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Required top-level keys for Flutter POS Drift tables
        $this->assertArrayHasKey('product_items', $data);
        $this->assertArrayHasKey('product_item_variant_group_options', $data);
        $this->assertArrayHasKey('inventory_item_variant_group_options', $data);

        $productItems = collect($data['product_items']);
        $pivotOptions = collect($data['product_item_variant_group_options']);
        $legacyPivots = collect($data['inventory_item_variant_group_options']);

        // Assert strictly scoped to Outlet A (3 items: 1 single + 2 variants)
        $this->assertCount(3, $productItems);
        $this->assertTrue($productItems->contains('id', $this->singleProductItem->id));
        $this->assertTrue($productItems->contains('id', $this->varItem1->id));
        $this->assertTrue($productItems->contains('id', $this->varItem2->id));

        // Outlet B product MUST NOT leak into Outlet A
        $this->assertFalse($productItems->contains('sku', 'EXC-B-01'));

        // Assert ProductItem attributes and unit
        $singleSynced = $productItems->firstWhere('id', $this->singleProductItem->id);
        $this->assertEquals('Americano Iced', $singleSynced['name']);
        $this->assertEquals('Cup', $singleSynced['unit']);
        $this->assertEquals('899000111', $singleSynced['barcode']);
        $this->assertFalse($singleSynced['track_inventory']);

        // Assert product_item_variant_group_options content
        $this->assertCount(2, $pivotOptions);
        $this->assertTrue($pivotOptions->contains(fn ($p) => $p['product_item_id'] === $this->varItem1->id && $p['variant_group_option_id'] === $this->opt1->id));
        $this->assertTrue($pivotOptions->contains(fn ($p) => $p['product_item_id'] === $this->varItem2->id && $p['variant_group_option_id'] === $this->opt2->id));

        // Assert legacy backward-compatibility inventory_item_variant_group_options content
        $this->assertCount(2, $legacyPivots);
        $this->assertArrayHasKey('inventory_item_id', $legacyPivots->first());
        $this->assertArrayHasKey('variant_group_option_id', $legacyPivots->first());
    }
}
