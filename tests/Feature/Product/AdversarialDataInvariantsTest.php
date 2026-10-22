<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\RecipeVersion;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdversarialDataInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $businessType = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Adversarial Test Business',
            'owner_name' => 'Owner',
            'email' => 'adv_'.uniqid().'@test.com',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);
    }

    /**
     * Test 1a: Verify that deleting a product_item cascades to delete its pivot entries.
     */
    public function test_deleting_product_item_cascades_to_pivot_entries(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adversarial Variant Product',
            'code' => 'ADV-P1',
            'product_type' => 'basic',
            'has_variant' => true,
        ]);

        $group = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Color',
        ]);

        $option = VariantGroupOption::create([
            'variant_group_id' => $group->id,
            'name' => 'Red',
            'sort_order' => 1,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Adversarial Variant Product - Red',
            'sku' => 'ADV-P1-RED',
            'is_active' => true,
        ]);

        // Attach pivot option
        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);

        $this->assertDatabaseHas('product_item_variant_group_options', [
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);

        // Delete product item (hard delete to trigger DB FK cascade)
        $item->forceDelete();

        // Verify cascade delete
        $this->assertDatabaseMissing('product_item_variant_group_options', [
            'product_item_id' => $item->id,
        ]);
    }

    /**
     * Test 1b: Verify that deleting a variant_group_option cascades to delete its pivot entries.
     */
    public function test_deleting_variant_group_option_cascades_to_pivot_entries(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adversarial Variant Product 2',
            'code' => 'ADV-P2',
            'product_type' => 'basic',
            'has_variant' => true,
        ]);

        $group = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Size',
        ]);

        $option = VariantGroupOption::create([
            'variant_group_id' => $group->id,
            'name' => 'Large',
            'sort_order' => 1,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Adversarial Variant Product 2 - Large',
            'sku' => 'ADV-P2-LG',
            'is_active' => true,
        ]);

        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);

        $this->assertDatabaseHas('product_item_variant_group_options', [
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);

        // Delete variant group option
        $option->delete();

        // Verify cascade delete
        $this->assertDatabaseMissing('product_item_variant_group_options', [
            'variant_group_option_id' => $option->id,
        ]);
    }

    /**
     * Test 1c: Verify composite primary key rejects duplicate (product_item_id, variant_group_option_id).
     */
    public function test_composite_primary_key_rejects_duplicate_pairs(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adversarial Variant Product 3',
            'code' => 'ADV-P3',
            'product_type' => 'basic',
            'has_variant' => true,
        ]);

        $group = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Flavor',
        ]);

        $option = VariantGroupOption::create([
            'variant_group_id' => $group->id,
            'name' => 'Vanilla',
            'sort_order' => 1,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Adversarial Variant Product 3 - Vanilla',
            'sku' => 'ADV-P3-VAN',
            'is_active' => true,
        ]);

        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);

        // Attempt duplicate insertion
        $this->expectException(QueryException::class);

        DB::table('product_item_variant_group_options')->insert([
            'product_item_id' => $item->id,
            'variant_group_option_id' => $option->id,
        ]);
    }

    /**
     * Test 2a: Verify that deleting a product_item cascades to delete corresponding recipe_versions.
     */
    public function test_deleting_product_item_cascades_to_recipe_versions(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adversarial Recipe Product',
            'code' => 'ADV-REC-1',
            'product_type' => 'basic',
            'has_variant' => true,
            'has_recipe' => true,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Adversarial Recipe Item',
            'sku' => 'ADV-REC-ITM1',
            'is_active' => true,
        ]);

        $recipeVersion = RecipeVersion::create([
            'product_id' => $product->id,
            'product_item_id' => $item->id,
            'version_number' => 1,
            'is_active' => true,
            'effective_from' => now(),
        ]);

        $this->assertDatabaseHas('recipe_versions', [
            'id' => $recipeVersion->id,
            'product_item_id' => $item->id,
        ]);

        // Delete product item (hard delete to trigger DB FK cascade)
        $item->forceDelete();

        // Verify cascade delete
        $this->assertDatabaseMissing('recipe_versions', [
            'id' => $recipeVersion->id,
        ]);
    }

    /**
     * Test 2b: Verify that assigning an invalid UUID to product_item_id throws a foreign key constraint violation.
     */
    public function test_assigning_invalid_uuid_to_recipe_version_product_item_id_throws_fk_violation(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adversarial Recipe Product 2',
            'code' => 'ADV-REC-2',
            'product_type' => 'basic',
            'has_variant' => false,
            'has_recipe' => true,
        ]);

        $this->expectException(QueryException::class);

        RecipeVersion::create([
            'product_id' => $product->id,
            'product_item_id' => (string) Str::uuid(), // Non-existent UUID
            'version_number' => 1,
            'is_active' => true,
            'effective_from' => now(),
        ]);
    }

    /**
     * Test 3a: Unified Item Invariant on single products.
     */
    public function test_single_product_items_have_null_variant_combination(): void
    {
        $singleProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Single Product Test',
            'code' => 'ADV-SNG-1',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);

        $defaultItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $singleProduct->id,
            'item_type' => 'variant_sku',
            'name' => 'Single Product Test',
            'sku' => 'ADV-SNG-1',
            'variant_combination' => null,
            'is_active' => true,
        ]);

        $this->assertNull($defaultItem->variant_combination);
        $this->assertFalse($singleProduct->has_variant);
    }

    /**
     * Test 3b: Verify backfill normalization sanitizes legacy dirty variant_combination on single products
     * while strictly preserving legitimate combinations on variant products.
     */
    public function test_normalization_sanitizes_dirty_variant_combinations_on_single_products_while_preserving_variant_products(): void
    {
        // 1. Single product with legacy dirty pseudo-hash
        $singleProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Legacy Aqua 600ml',
            'code' => 'ADV-LEG-AQUA',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);

        $dirtySingleItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $singleProduct->id,
            'item_type' => 'variant_sku',
            'name' => 'Legacy Aqua 600ml',
            'sku' => 'ADV-LEG-AQUA',
            'variant_combination' => 'A006almqu',
            'is_active' => true,
        ]);

        // 2. Variant product with legitimate combination
        $variantProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'T-Shirt Cotton',
            'code' => 'ADV-VAR-TSHIRT',
            'product_type' => 'basic',
            'has_variant' => true,
        ]);

        $legitimateVariantItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $variantProduct->id,
            'item_type' => 'variant_sku',
            'name' => 'T-Shirt Cotton - Red / L',
            'sku' => 'ADV-VAR-TSHIRT-RED-L',
            'variant_combination' => 'Red / L',
            'is_active' => true,
        ]);

        $this->assertEquals('A006almqu', $dirtySingleItem->variant_combination);
        $this->assertEquals('Red / L', $legitimateVariantItem->variant_combination);

        // Execute database-agnostic subquery normalization (identical to migration 100003)
        $now = now();
        DB::table('product_items')
            ->whereIn('product_id', function ($query) {
                $query->select('id')
                    ->from('products')
                    ->where('has_variant', false);
            })
            ->whereNotNull('variant_combination')
            ->update([
                'variant_combination' => null,
                'updated_at' => $now,
            ]);

        // Verify single product dirty string is wiped to null
        $this->assertNull($dirtySingleItem->fresh()->variant_combination);

        // Verify variant product legitimate combination is preserved unchanged
        $this->assertEquals('Red / L', $legitimateVariantItem->fresh()->variant_combination);
    }
}
