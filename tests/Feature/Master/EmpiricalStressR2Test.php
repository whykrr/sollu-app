<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\ModifierGroup;
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
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmpiricalStressR2Test extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected BusinessType $businessType;

    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $this->businessType = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->uom = Uom::firstOrCreate(
            ['code' => 'Pcs'],
            ['name' => 'Pieces', 'category' => 'count']
        );
    }

    /**
     * Helper to provision a merchant, subscription, outlet, and fully-permissioned user.
     */
    protected function createMerchant(PlanEnum $planEnum, string $namePrefix = 'Merchant'): array
    {
        $business = Business::create([
            'name' => "{$namePrefix} {$planEnum->value}",
            'owner_name' => "Owner {$namePrefix}",
            'email' => 'emp_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $this->businessType->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        if ($plan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $business->clearMemoizedFeatures();

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => "{$namePrefix} Main Outlet",
            'is_active' => true,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => "{$namePrefix} User",
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        foreach (PermissionEnum::cases() as $perm) {
            Permission::firstOrCreate(['name' => $perm->value, 'guard_name' => 'business']);
            $user->givePermissionTo($perm->value);
        }

        return [$business, $user, $outlet];
    }

    // =========================================================================
    // 1. Plan Feature Gating: Micro Plan vs Pro Plan
    // =========================================================================

    public function test_micro_plan_user_strictly_forbidden_from_modifier_endpoints_all_verbs(): void
    {
        [$business, $user, $outlet] = $this->createMerchant(PlanEnum::MICRO, 'MicroStore');

        // Existing modifier in another business to test PUT/DELETE path resolution
        $dummyModifier = ModifierGroup::create([
            'business_id' => $business->id,
            'name' => 'Micro Fake Mod',
            'selection_type' => 'single',
            'is_required' => false,
        ]);

        // 1. GET index -> 403
        $respGet = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/modifiers");
        $respGet->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);

        // 2. POST create -> 403
        $respPost = $this->actingAs($user, 'business')
            ->postJson("http://{$this->appDomain}/master/modifiers", [
                'name' => 'Extra Cheese',
                'selection_type' => 'single',
                'options' => [['name' => 'Cheddar', 'additional_price' => 3000]],
            ]);
        $respPost->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);

        // 3. PUT update -> 403
        $respPut = $this->actingAs($user, 'business')
            ->putJson("http://{$this->appDomain}/master/modifiers/{$dummyModifier->id}", [
                'name' => 'Updated Cheese',
                'selection_type' => 'single',
                'options' => [['name' => 'Mozzarella', 'additional_price' => 5000]],
            ]);
        $respPut->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);

        // 4. DELETE destroy -> 403
        $respDelete = $this->actingAs($user, 'business')
            ->deleteJson("http://{$this->appDomain}/master/modifiers/{$dummyModifier->id}");
        $respDelete->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);
    }

    public function test_micro_plan_user_strictly_forbidden_from_recipe_and_raw_material_endpoints(): void
    {
        [$business, $user, $outlet] = $this->createMerchant(PlanEnum::MICRO, 'MicroStoreRecipes');

        // GET recipes index -> 403
        $respRecipe = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/recipes");
        $respRecipe->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);

        // GET raw-materials index -> 403
        $respRaw = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/raw-materials");
        $respRaw->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);

        // POST raw-materials -> 403
        $respPostRaw = $this->actingAs($user, 'business')
            ->postJson("http://{$this->appDomain}/master/raw-materials", [
                'name' => 'Coffee Beans Arabica',
                'uom_id' => $this->uom->id,
            ]);
        $respPostRaw->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);
    }

    public function test_pro_plan_user_has_unlocked_access_to_modifiers_and_recipes(): void
    {
        [$business, $user, $outlet] = $this->createMerchant(PlanEnum::PRO, 'ProStore');

        // 1. Modifiers index accessible (not 403)
        $respMod = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/master/modifiers");
        $this->assertNotEquals(403, $respMod->status());

        // 2. Modifiers create succeeds
        $respCreateMod = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/master/modifiers", [
                'name' => 'Sugar Level',
                'selection_type' => 'single',
                'is_required' => true,
                'options' => [
                    ['name' => 'Normal Sugar (100%)', 'additional_price' => 0, 'is_default' => true],
                    ['name' => 'Less Sugar (50%)', 'additional_price' => 0, 'is_default' => false],
                ],
            ]);
        $respCreateMod->assertStatus(302);
        $respCreateMod->assertSessionHas('success');

        $this->assertDatabaseHas('modifier_groups', [
            'business_id' => $business->id,
            'name' => 'Sugar Level',
        ]);

        // 3. Raw materials & recipes accessible
        $respRaw = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/master/raw-materials");
        $this->assertNotEquals(403, $respRaw->status());

        $respRecipe = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/master/recipes");
        $this->assertNotEquals(403, $respRecipe->status());
    }

    // =========================================================================
    // 2. Multi-Tenant Isolation on ModifierGroup and Services
    // =========================================================================

    public function test_cross_tenant_modifier_group_access_strictly_returns_403_and_preserves_data(): void
    {
        [$businessA, $userA, $outletA] = $this->createMerchant(PlanEnum::PRO, 'TenantA');
        [$businessB, $userB, $outletB] = $this->createMerchant(PlanEnum::PRO, 'TenantB_Attacker');

        // Tenant A creates modifier group
        $modGroupA = ModifierGroup::create([
            'business_id' => $businessA->id,
            'name' => 'Topping tenant A Original',
            'selection_type' => 'multi',
            'max_select' => 3,
            'is_required' => false,
        ]);
        $modOptionA = $modGroupA->options()->create([
            'name' => 'Boba Pearl',
            'additional_price' => 4000,
            'is_default' => false,
        ]);

        // 1. Tenant B tries to show Tenant A's modifier
        $showResp = $this->actingAs($userB, 'business')
            ->getJson("http://{$this->appDomain}/master/modifiers/{$modGroupA->id}");
        $showResp->assertStatus(403);

        // 2. Tenant B tries to update Tenant A's modifier
        $updateResp = $this->actingAs($userB, 'business')
            ->putJson("http://{$this->appDomain}/master/modifiers/{$modGroupA->id}", [
                'name' => 'Hacked by Tenant B',
                'selection_type' => 'single',
                'is_required' => true,
                'options' => [
                    ['name' => 'Malicious Option', 'additional_price' => 999999],
                ],
            ]);
        $updateResp->assertStatus(403);

        // 3. Tenant B tries to delete Tenant A's modifier
        $deleteResp = $this->actingAs($userB, 'business')
            ->deleteJson("http://{$this->appDomain}/master/modifiers/{$modGroupA->id}");
        $deleteResp->assertStatus(403);

        // Assert Tenant A data remains completely intact
        $modGroupA->refresh();
        $this->assertEquals('Topping tenant A Original', $modGroupA->name);
        $this->assertEquals('multi', $modGroupA->selection_type);
        $this->assertDatabaseHas('modifier_groups', ['id' => $modGroupA->id]);
        $this->assertDatabaseHas('modifier_options', ['id' => $modOptionA->id, 'name' => 'Boba Pearl']);
    }

    public function test_cross_tenant_service_product_destroy_strictly_returns_403(): void
    {
        [$businessA, $userA, $outletA] = $this->createMerchant(PlanEnum::PRO, 'TenantA_Service');
        [$businessB, $userB, $outletB] = $this->createMerchant(PlanEnum::PRO, 'TenantB_ServiceAttacker');

        $serviceA = Product::create([
            'business_id' => $businessA->id,
            'name' => 'Haircut Treatment VIP',
            'code' => 'SRV-VIP',
            'product_type' => 'service',
            'is_show' => true,
            'sellable' => true,
            'track_inventory' => false,
        ]);

        $itemA = ProductItem::create([
            'business_id' => $businessA->id,
            'product_id' => $serviceA->id,
            'item_type' => 'variant_sku',
            'name' => 'Haircut Treatment VIP',
            'sku' => 'SRV-VIP',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
            'track_inventory' => false,
        ]);

        // Tenant B attempts to destroy Tenant A's service
        $resp = $this->actingAs($userB, 'business')
            ->delete("http://{$this->appDomain}/master/services/{$serviceA->id}");

        $resp->assertStatus(403);

        // Verify serviceA and itemA remain alive
        $this->assertFalse($serviceA->fresh()->trashed());
        $this->assertFalse($itemA->fresh()->trashed());
    }

    // =========================================================================
    // 3. Master Data Sync Payload Integrity and Relational Consistency
    // =========================================================================

    public function test_master_data_sync_exports_valid_product_items_and_relational_pivot_options(): void
    {
        [$business, $user, $outlet1] = $this->createMerchant(PlanEnum::PRO, 'SyncMerchant');

        // Create second outlet for same business
        $outlet2 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet 2 - Remote',
            'is_active' => true,
        ]);

        // POS Device paired to Outlet 1
        $device1 = OutletDevice::create([
            'outlet_id' => $outlet1->id,
            'device_name' => 'Cashier POS 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-uuid-outlet1-01',
            'hardware_fingerprint' => 'hw-fingerprint-outlet1-01',
            'is_active' => true,
        ]);
        app(PosDeviceAuthCacheService::class)->putDevice($device1);

        // Product 1: Single item attached to Outlet 1
        $singleProd = Product::create([
            'business_id' => $business->id,
            'name' => 'Espresso Single',
            'code' => 'ESP-001',
            'product_type' => 'basic',
            'has_variant' => false,
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
        ]);
        $singleProd->outlets()->attach($outlet1->id, ['is_enabled' => true, 'is_available' => true]);

        $singleItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $singleProd->id,
            'uom_id' => $this->uom->id,
            'item_type' => 'variant_sku',
            'name' => 'Espresso Single',
            'sku' => 'ESP-001',
            'barcode' => '1122334455',
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);
        ProductPrice::create([
            'product_id' => $singleProd->id,
            'product_item_id' => $singleItem->id,
            'amount' => 15000,
        ]);

        // Product 2: Multi-variant product with 2 groups (Size x Temperature) -> 4 items
        $varProd = Product::create([
            'business_id' => $business->id,
            'name' => 'Signature Latte',
            'code' => 'LAT-001',
            'product_type' => 'basic',
            'has_variant' => true,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $varProd->outlets()->attach($outlet1->id, ['is_enabled' => true, 'is_available' => true]);

        $groupSize = VariantGroup::create(['product_id' => $varProd->id, 'name' => 'Size']);
        $optSmall = VariantGroupOption::create(['variant_group_id' => $groupSize->id, 'name' => 'Small']);
        $optLarge = VariantGroupOption::create(['variant_group_id' => $groupSize->id, 'name' => 'Large']);

        $groupTemp = VariantGroup::create(['product_id' => $varProd->id, 'name' => 'Temperature']);
        $optHot = VariantGroupOption::create(['variant_group_id' => $groupTemp->id, 'name' => 'Hot']);
        $optIce = VariantGroupOption::create(['variant_group_id' => $groupTemp->id, 'name' => 'Ice']);

        $combinations = [
            ['name' => 'Small Hot', 'opts' => [$optSmall, $optHot], 'sku' => 'LAT-S-HOT', 'barcode' => '2001'],
            ['name' => 'Small Ice', 'opts' => [$optSmall, $optIce], 'sku' => 'LAT-S-ICE', 'barcode' => '2002'],
            ['name' => 'Large Hot', 'opts' => [$optLarge, $optHot], 'sku' => 'LAT-L-HOT', 'barcode' => '2003'],
            ['name' => 'Large Ice', 'opts' => [$optLarge, $optIce], 'sku' => 'LAT-L-ICE', 'barcode' => '2004'],
        ];

        $varItemIds = [];
        foreach ($combinations as $comb) {
            $vItem = ProductItem::create([
                'business_id' => $business->id,
                'product_id' => $varProd->id,
                'uom_id' => $this->uom->id,
                'item_type' => 'variant_sku',
                'name' => "Signature Latte - {$comb['name']}",
                'variant_combination' => $comb['name'],
                'sku' => $comb['sku'],
                'barcode' => $comb['barcode'],
                'track_inventory' => true,
                'is_show' => true,
                'sellable' => true,
                'is_active' => true,
            ]);
            $varItemIds[] = $vItem->id;

            foreach ($comb['opts'] as $opt) {
                DB::table('product_item_variant_group_options')->insert([
                    'product_item_id' => $vItem->id,
                    'variant_group_option_id' => $opt->id,
                ]);
            }
        }

        // Product 3: Attached ONLY to Outlet 2 (MUST NOT leak into Outlet 1 sync)
        $prodOutlet2 = Product::create([
            'business_id' => $business->id,
            'name' => 'Outlet 2 Exclusive Snack',
            'code' => 'SNK-002',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);
        $prodOutlet2->outlets()->attach($outlet2->id, ['is_enabled' => true, 'is_available' => true]);
        ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $prodOutlet2->id,
            'item_type' => 'variant_sku',
            'name' => 'Outlet 2 Exclusive Snack',
            'sku' => 'SNK-002',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Product 4: Cross-tenant product from completely different business (MUST NOT leak)
        [$businessOther] = $this->createMerchant(PlanEnum::PRO, 'OtherMerchant');
        $otherProd = Product::create([
            'business_id' => $businessOther->id,
            'name' => 'Other Tenant Product',
            'code' => 'OTH-001',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);
        ProductItem::create([
            'business_id' => $businessOther->id,
            'product_id' => $otherProd->id,
            'item_type' => 'variant_sku',
            'name' => 'Other Tenant Product',
            'sku' => 'OTH-001',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Execute Sync Request as Device 1
        Sanctum::actingAs($device1, ['pos:access']);
        $resp = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-uuid-outlet1-01',
            'X-HARDWARE-SIGNATURE' => 'hw-fingerprint-outlet1-01',
        ])->getJson('http://api.sollu.test/pos/sync/master?force=1');

        $resp->assertStatus(200);

        $payload = $resp->json('data');

        // Required structural keys
        $this->assertArrayHasKey('product_items', $payload);
        $this->assertArrayHasKey('product_item_variant_group_options', $payload);
        $this->assertArrayHasKey('variant_groups', $payload);
        $this->assertArrayHasKey('variant_group_options', $payload);

        $productItems = collect($payload['product_items']);
        $pivots = collect($payload['product_item_variant_group_options']);

        // Assert Exactly 5 items exported (1 single + 4 variants)
        $this->assertCount(5, $productItems);

        // Assert single item presence & attribute resolution
        $exportedSingle = $productItems->firstWhere('sku', 'ESP-001');
        $this->assertNotNull($exportedSingle);
        $this->assertEquals('Espresso Single', $exportedSingle['name']);
        $this->assertEquals('Pieces', $exportedSingle['unit']);
        $this->assertEquals('1122334455', $exportedSingle['barcode']);
        $this->assertFalse($exportedSingle['track_inventory']);
        $this->assertArrayNotHasKey('business_id', $exportedSingle, 'business_id must not leak in POS payload');

        // Assert all 4 variant items present
        foreach (['LAT-S-HOT', 'LAT-S-ICE', 'LAT-L-HOT', 'LAT-L-ICE'] as $expectedSku) {
            $this->assertTrue($productItems->contains('sku', $expectedSku), "Variant SKU {$expectedSku} must be exported");
        }

        // Assert NO cross-outlet leak
        $this->assertFalse($productItems->contains('sku', 'SNK-002'), 'Outlet 2 item leaked into Outlet 1 sync payload');

        // Assert NO cross-tenant leak
        $this->assertFalse($productItems->contains('sku', 'OTH-001'), 'Foreign tenant item leaked into sync payload');

        // Assert Relational Integrity of product_item_variant_group_options:
        // 4 variant items * 2 options each = 8 pivot records
        $this->assertCount(8, $pivots);

        $exportedItemIds = $productItems->pluck('id')->all();
        $exportedOptionIds = collect($payload['variant_group_options'])->pluck('id')->all();

        foreach ($pivots as $pivotRow) {
            $this->assertArrayHasKey('product_item_id', $pivotRow);
            $this->assertArrayHasKey('variant_group_option_id', $pivotRow);

            // Foreign key consistency: product_item_id MUST exist in exported product_items
            $this->assertContains($pivotRow['product_item_id'], $exportedItemIds);

            // Foreign key consistency: variant_group_option_id MUST exist in exported variant_group_options
            $this->assertContains($pivotRow['variant_group_option_id'], $exportedOptionIds);
        }
    }
}
