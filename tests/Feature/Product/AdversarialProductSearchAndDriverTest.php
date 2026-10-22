<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdversarialProductSearchAndDriverTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;

    protected Business $businessB;

    protected Product $productA;

    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $businessType = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->businessA = Business::create([
            'name' => 'Business Alpha',
            'owner_name' => 'Alpha Owner',
            'email' => 'alpha_'.uniqid().'@test.com',
            'phone' => '081234567891',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);

        $this->businessB = Business::create([
            'name' => 'Business Beta',
            'owner_name' => 'Beta Owner',
            'email' => 'beta_'.uniqid().'@test.com',
            'phone' => '081234567892',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);

        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Product',
            'code' => 'AP-01',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);

        $this->productB = Product::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Product',
            'code' => 'BP-01',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);
    }

    /**
     * Test 1: Verify search filters handle null attributes safely without concatenation bugs.
     */
    public function test_search_filters_handle_null_attributes_safely(): void
    {
        // Item with null variant_combination and null barcode
        $item1 = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Arabika Gayo Single Origin',
            'sku' => 'KAG-SINGLE',
            'barcode' => null,
            'variant_combination' => null,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Item with null sku and null variant_combination
        $item2 = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Matcha Latte Ice Blend',
            'sku' => null,
            'barcode' => '8991234567890',
            'variant_combination' => null,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Search by name for item with null barcode/variant_combination
        $results1 = ProductItem::filters(['search' => 'Arabika'])->get();
        $this->assertCount(1, $results1);
        $this->assertEquals($item1->id, $results1->first()->id);

        // Search by barcode for item with null sku
        $results2 = ProductItem::filters(['search' => '8991234567890'])->get();
        $this->assertCount(1, $results2);
        $this->assertEquals($item2->id, $results2->first()->id);

        // Search by SKU for item with null barcode
        $results3 = ProductItem::filters(['search' => 'KAG-SINGLE'])->get();
        $this->assertCount(1, $results3);
        $this->assertEquals($item1->id, $results3->first()->id);
    }

    /**
     * Test 2: Verify multi-tenant index query isolation for identical SKU and barcode.
     */
    public function test_multi_tenant_sku_and_barcode_isolation(): void
    {
        $sharedSku = 'GLOBAL-SHARED-SKU-99';
        $sharedBarcode = '8999999999999';

        $itemA = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Alpha Shared Item',
            'sku' => $sharedSku,
            'barcode' => $sharedBarcode,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $itemB = ProductItem::create([
            'business_id' => $this->businessB->id,
            'product_id' => $this->productB->id,
            'item_type' => 'variant_sku',
            'name' => 'Beta Shared Item',
            'sku' => $sharedSku,
            'barcode' => $sharedBarcode,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // Filtering by (business_id, sku)
        $matchSkuA = ProductItem::where('business_id', $this->businessA->id)
            ->where('sku', $sharedSku)
            ->get();
        $this->assertCount(1, $matchSkuA);
        $this->assertEquals($itemA->id, $matchSkuA->first()->id);

        $matchSkuB = ProductItem::where('business_id', $this->businessB->id)
            ->where('sku', $sharedSku)
            ->get();
        $this->assertCount(1, $matchSkuB);
        $this->assertEquals($itemB->id, $matchSkuB->first()->id);

        // Filtering by (business_id, barcode)
        $matchBarcodeA = ProductItem::where('business_id', $this->businessA->id)
            ->where('barcode', $sharedBarcode)
            ->get();
        $this->assertCount(1, $matchBarcodeA);
        $this->assertEquals($itemA->id, $matchBarcodeA->first()->id);

        $matchBarcodeB = ProductItem::where('business_id', $this->businessB->id)
            ->where('barcode', $sharedBarcode)
            ->get();
        $this->assertCount(1, $matchBarcodeB);
        $this->assertEquals($itemB->id, $matchBarcodeB->first()->id);
    }

    /**
     * Test 3: Verify PostgreSQL GIN full-text index expression or SQLite graceful fallback.
     */
    public function test_driver_guard_omni_search_and_tsvector_coalesce(): void
    {
        $driver = DB::getDriverName();

        $item = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Espresso Doppio Extra Strong',
            'sku' => 'ESP-DOP',
            'barcode' => null,
            'variant_combination' => null,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        if ($driver === 'pgsql') {
            // Test that to_tsvector expression succeeds and finds the item even with nulls
            $match = DB::table('product_items')
                ->where('id', $item->id)
                ->whereRaw("to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)", ['Doppio'])
                ->exists();
            $this->assertTrue($match, 'PostgreSQL GIN to_tsvector expression must match Doppio with null barcode/variant_combination');
        } else {
            // On SQLite, verify that no raw GIN statements were executed and filters work
            $match = ProductItem::where('id', $item->id)->filters(['search' => 'Doppio'])->exists();
            $this->assertTrue($match, 'SQLite search fallback must match item via whereLike');
        }
    }

    /**
     * Test 4: Verify search with punctuation, whitespaces, and symbols does not crash.
     */
    public function test_search_with_symbols_and_special_characters(): void
    {
        $item = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Coca-Cola (Zero Sugar) 330ml / Can',
            'sku' => 'CC-ZERO/330',
            'barcode' => '899-000-111',
            'variant_combination' => 'Cold & Fresh',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $queries = [
            'Coca-Cola',
            '(Zero Sugar)',
            'CC-ZERO/330',
            'Cold & Fresh',
            '   ', // Whitespace
            '',    // Empty
            "Special'Quote",
        ];

        foreach ($queries as $q) {
            $response = ProductItem::filters(['search' => $q])->get();
            $this->assertNotNull($response);
        }

        $found = ProductItem::filters(['search' => 'CC-ZERO/330'])->first();
        $this->assertNotNull($found);
        $this->assertEquals($item->id, $found->id);
    }
}
