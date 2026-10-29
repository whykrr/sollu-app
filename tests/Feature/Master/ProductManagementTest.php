<?php

namespace Tests\Feature\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ModifierGroup;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Master\ProductService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductManagementTest extends TestCase
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

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Unified Product Merchant',
            'owner_name' => 'Owner Merchant',
            'email' => 'unified_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
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
            'name' => 'Outlet Kemang Pusat',
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'Pcs'],
            ['name' => 'Pcs', 'category' => 'count']
        );

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Inventory Manager',
            'email' => 'manager_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::PRODUCT_CREATE->value,
            PermissionEnum::PRODUCT_UPDATE->value,
            PermissionEnum::PRODUCT_DELETE->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_creates_single_product_with_default_product_item_and_decoupled_inventory(): void
    {
        $payload = [
            'name' => 'Kopi Robusta Single',
            'code' => 'KOP-SGL',
            'product_type' => 'basic',
            'base_price' => 18000,
            'track_inventory' => false,
            'has_variant' => false,
            'has_recipe' => false,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'barcode' => '899123456001',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $product = Product::where('code', 'KOP-SGL')->first();
        $this->assertNotNull($product);
        $this->assertFalse((bool) $product->track_inventory);
        $this->assertTrue((bool) $product->sellable);
        $this->assertTrue((bool) $product->is_show);

        // Unified Item Pattern: Must have exactly 1 default ProductItem
        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(1, $items);

        $defaultItem = $items->first();
        $this->assertEquals('Kopi Robusta Single', $defaultItem->name);
        $this->assertNull($defaultItem->variant_combination);
        $this->assertEquals('KOP-SGL', $defaultItem->sku);
        $this->assertEquals('899123456001', $defaultItem->barcode);
        $this->assertFalse((bool) $defaultItem->track_inventory);
        $this->assertTrue((bool) $defaultItem->sellable);
        $this->assertTrue((bool) $defaultItem->is_show);

        // Price must be linked
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_item_id' => $defaultItem->id,
            'amount' => 18000,
        ]);

        // Total Decoupling: Zero inventory rows created
        $this->assertDatabaseMissing('inventory_items', [
            'product_item_id' => $defaultItem->id,
        ]);
        $this->assertEquals(0, InventoryItem::where('product_item_id', $defaultItem->id)->count());
    }

    public function test_creates_single_product_with_linked_inventory_when_track_inventory_is_true(): void
    {
        $payload = [
            'name' => 'Biji Kopi Arabika 250g',
            'code' => 'BIJI-ARB',
            'product_type' => 'basic',
            'base_price' => 75000,
            'track_inventory' => true,
            'has_variant' => false,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'minimum_stock' => 5,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));

        $product = Product::where('code', 'BIJI-ARB')->first();
        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->track_inventory);

        $defaultItem = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($defaultItem);
        $this->assertTrue((bool) $defaultItem->track_inventory);

        // Must create inventory_item and initial balances for active outlet
        $invItem = InventoryItem::where('product_item_id', $defaultItem->id)->first();
        $this->assertNotNull($invItem);
        $this->assertEquals($this->business->id, $invItem->business_id);

        $balance = InventoryBalance::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();
        $this->assertNotNull($balance);
    }

    public function test_creates_variant_product_with_variant_matrix_and_pivot_normalization(): void
    {
        $payload = [
            'name' => 'Kopi Susu Gula Aren',
            'code' => 'KOP-AREN',
            'product_type' => 'basic',
            'base_price' => 20000,
            'has_variant' => true,
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
            'uom_id' => $this->uom->id,
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'Regular'],
                        ['name' => 'Large'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'Regular'],
                    'sku' => 'KOP-AREN-REG',
                    'barcode' => '89999001',
                    'price' => 20000,
                    'sellable' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Ukuran' => 'Large'],
                    'sku' => 'KOP-AREN-LRG',
                    'barcode' => '89999002',
                    'price' => 25000,
                    'sellable' => true,
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));

        $product = Product::where('code', 'KOP-AREN')->first();
        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->has_variant);

        // Check Variant Groups & Options
        $group = VariantGroup::where('product_id', $product->id)->first();
        $this->assertEquals('Ukuran', $group->name);
        $options = VariantGroupOption::where('variant_group_id', $group->id)->get();
        $this->assertCount(2, $options);

        // Check ProductItems
        $items = ProductItem::where('product_id', $product->id)->get();
        $this->assertCount(2, $items);

        $itemReg = $items->firstWhere('sku', 'KOP-AREN-REG');
        $itemLrg = $items->firstWhere('sku', 'KOP-AREN-LRG');
        $this->assertNotNull($itemReg);
        $this->assertNotNull($itemLrg);

        // Pivot normalization: product_item_variant_group_options must link item to options
        $pivots = DB::table('product_item_variant_group_options')
            ->whereIn('product_item_id', [$itemReg->id, $itemLrg->id])
            ->get();
        $this->assertCount(2, $pivots);

        $regOption = $options->firstWhere('name', 'Regular');
        $lrgOption = $options->firstWhere('name', 'Large');

        $this->assertTrue($pivots->contains(fn ($p) => $p->product_item_id === $itemReg->id && $p->variant_group_option_id === $regOption->id));
        $this->assertTrue($pivots->contains(fn ($p) => $p->product_item_id === $itemLrg->id && $p->variant_group_option_id === $lrgOption->id));

        // Prices must be saved per item
        $this->assertDatabaseHas('product_prices', [
            'product_item_id' => $itemReg->id,
            'amount' => 20000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_item_id' => $itemLrg->id,
            'amount' => 25000,
        ]);
    }

    public function test_causality_enforcement_sellable_false_strictly_sets_is_show_false_on_create_and_update(): void
    {
        // 1. Create with sellable = false and is_show = true
        $createPayload = [
            'name' => 'Bahan Setengah Jadi Kopi',
            'code' => 'BSJ-001',
            'product_type' => 'basic',
            'base_price' => 10000,
            'track_inventory' => false,
            'has_variant' => false,
            'sellable' => false,
            'is_show' => true, // Attempt to show, system must override to false
            'uom_id' => $this->uom->id,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $createPayload);

        $response->assertRedirect(route('master.products.index'));

        $product = Product::where('code', 'BSJ-001')->first();
        $this->assertFalse((bool) $product->sellable);
        $this->assertFalse((bool) $product->is_show, 'Kausalitas gagal: sellable=false harus memaksa is_show=false');

        $item = ProductItem::where('product_id', $product->id)->first();
        $this->assertFalse((bool) $item->sellable);
        $this->assertFalse((bool) $item->is_show);

        // 2. Update existing sellable product to unsellable
        $product->update(['sellable' => true, 'is_show' => true]);
        $item->update(['sellable' => true, 'is_show' => true]);

        $updatePayload = [
            'name' => 'Bahan Setengah Jadi Kopi Renamed',
            'code' => 'BSJ-001',
            'product_type' => 'basic',
            'base_price' => 12000,
            'track_inventory' => false,
            'has_variant' => false,
            'sellable' => false,
            'is_show' => true, // Attempt to keep is_show = true
            'uom_id' => $this->uom->id,
        ];

        $updateResponse = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);

        $updateResponse->assertRedirect(route('master.products.index'));

        $product->refresh();
        $item->refresh();
        $this->assertFalse((bool) $product->sellable);
        $this->assertFalse((bool) $product->is_show);
        $this->assertFalse((bool) $item->sellable);
        $this->assertFalse((bool) $item->is_show);
    }

    public function test_creates_service_product_without_inventory(): void
    {
        $payload = [
            'name' => 'Jasa Servis Mesin Kopi',
            'code' => 'SRV-001',
            'base_price' => 150000,
            'is_show' => true,
            'sellable' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/services", $payload);

        $response->assertRedirect(route('master.services.index'));

        $service = Product::where('code', 'SRV-001')->first();
        $this->assertNotNull($service);
        $this->assertEquals('service', $service->product_type->value ?? $service->product_type);
        $this->assertFalse((bool) $service->track_inventory);

        $items = ProductItem::where('product_id', $service->id)->get();
        $this->assertCount(1, $items);
        $this->assertFalse((bool) $items->first()->track_inventory);

        $this->assertEquals(0, InventoryItem::where('product_item_id', $items->first()->id)->count());
    }

    public function test_soft_deleting_product_soft_deletes_its_product_items_and_deactivates_inventory_items(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi To Delete',
            'code' => 'DEL-001',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi To Delete',
            'sku' => 'DEL-001',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $item->id,
            'name' => 'Kopi To Delete',
            'sku' => 'DEL-001',
            'uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/master/products/{$product->id}");

        $response->assertStatus(302);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('product_items', ['id' => $item->id]);
        $this->assertFalse((bool) $invItem->fresh()->is_active, 'InventoryItem must be deactivated when product is soft-deleted');
    }

    public function test_variant_product_update_to_unsellable_cascades_to_all_child_product_items(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Susu Variant',
            'code' => 'VAR-001',
            'product_type' => 'basic',
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
        ]);

        $item1 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Susu - Regular',
            'sku' => 'VAR-REG',
            'variant_combination' => 'Regular',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $item2 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Susu - Large',
            'sku' => 'VAR-LRG',
            'variant_combination' => 'Large',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Update via ProductService without sending variants payload
        app(ProductService::class)->updateProduct($product, [
            'sellable' => false,
            'name' => 'Kopi Susu Variant Updated',
        ]);

        $product->refresh();
        $this->assertFalse((bool) $product->sellable);
        $this->assertFalse((bool) $product->is_show);

        $this->assertFalse((bool) $item1->fresh()->sellable, 'Child item 1 must be sellable = false');
        $this->assertFalse((bool) $item1->fresh()->is_show, 'Child item 1 must be is_show = false');
        $this->assertFalse((bool) $item2->fresh()->sellable, 'Child item 2 must be sellable = false');
        $this->assertFalse((bool) $item2->fresh()->is_show, 'Child item 2 must be is_show = false');

        // Test direct Eloquent update also cascades via static::saved hook
        $product->update([
            'sellable' => true,
            'is_show' => true,
        ]);
        $item1->update(['sellable' => true, 'is_show' => true]);
        $item2->update(['sellable' => true, 'is_show' => true]);

        $product->update(['sellable' => false]);
        $this->assertFalse((bool) $item1->fresh()->sellable);
        $this->assertFalse((bool) $item1->fresh()->is_show);
        $this->assertFalse((bool) $item2->fresh()->sellable);
        $this->assertFalse((bool) $item2->fresh()->is_show);
    }

    public function test_modifier_group_controller_tenant_isolation_rejects_cross_tenant_access(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Victim Merchant',
            'owner_name' => 'Victim Owner',
            'email' => 'victim_'.uniqid().'@test.test',
            'phone' => '089876543210',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherModifier = ModifierGroup::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Tenant Modifier',
            'selection_type' => 'single',
            'is_required' => false,
        ]);

        // Cross-tenant update must return 403
        $updateResp = $this->actingAs($this->user, 'business')
            ->putJson("http://{$this->appDomain}/master/modifiers/{$otherModifier->id}", [
                'name' => 'Hacked Modifier Name',
                'selection_type' => 'single',
                'is_required' => false,
                'options' => [
                    ['name' => 'Hacked Option', 'additional_price' => 5000],
                ],
            ]);
        $updateResp->assertStatus(403);
        $this->assertEquals('Other Tenant Modifier', $otherModifier->fresh()->name);

        // Cross-tenant destroy must return 403
        $deleteResp = $this->actingAs($this->user, 'business')
            ->deleteJson("http://{$this->appDomain}/master/modifiers/{$otherModifier->id}");
        $deleteResp->assertStatus(403);
        $this->assertDatabaseHas('modifier_groups', ['id' => $otherModifier->id]);
    }

    public function test_service_product_deletion_delegates_to_delete_service_and_soft_deletes_items(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Facial Treatment Gold',
            'code' => 'SRV-FCL',
            'product_type' => 'service',
            'is_show' => true,
            'sellable' => true,
            'track_inventory' => false,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $service->id,
            'item_type' => 'variant_sku',
            'name' => 'Facial Treatment Gold',
            'sku' => 'SRV-FCL',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
            'track_inventory' => false,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/master/services/{$service->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertSoftDeleted('products', ['id' => $service->id]);
        $this->assertSoftDeleted('product_items', ['id' => $item->id]);
    }
}
