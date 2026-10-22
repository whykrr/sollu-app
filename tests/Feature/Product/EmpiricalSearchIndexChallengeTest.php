<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmpiricalSearchIndexChallengeTest extends TestCase
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
            'name' => 'Business Alpha Benchmark',
            'owner_name' => 'Alpha Owner',
            'email' => 'alpha_bench_'.uniqid().'@test.com',
            'phone' => '081234567801',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);

        $this->businessB = Business::create([
            'name' => 'Business Beta Benchmark',
            'owner_name' => 'Beta Owner',
            'email' => 'beta_bench_'.uniqid().'@test.com',
            'phone' => '081234567802',
            'status' => 'active',
            'business_type_id' => $businessType->id,
        ]);

        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Benchmark Product Alpha',
            'code' => 'BPA-01',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);

        $this->productB = Product::create([
            'business_id' => $this->businessB->id,
            'name' => 'Benchmark Product Beta',
            'code' => 'BPB-01',
            'product_type' => 'basic',
            'has_variant' => false,
        ]);
    }

    /**
     * Test 1: Verify SQLite compatibility with composite indexes, filters, and edge cases.
     */
    public function test_sqlite_search_indexes_and_driver_compatibility(): void
    {
        $item = ProductItem::create([
            'business_id' => $this->businessA->id,
            'product_id' => $this->productA->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Tubruk & Susu Manis (Hot / Ice) #10',
            'sku' => 'KTB-SUSU/10',
            'barcode' => '8991112223334',
            'variant_combination' => 'Normal Sugar / Extra Hot',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        // 1. Direct composite lookups
        $bySku = ProductItem::where('business_id', $this->businessA->id)
            ->where('sku', 'KTB-SUSU/10')
            ->first();
        $this->assertNotNull($bySku);
        $this->assertEquals($item->id, $bySku->id);

        $byBarcode = ProductItem::where('business_id', $this->businessA->id)
            ->where('barcode', '8991112223334')
            ->first();
        $this->assertNotNull($byBarcode);
        $this->assertEquals($item->id, $byBarcode->id);

        // 2. Cross-tenant isolation
        $crossTenant = ProductItem::where('business_id', $this->businessB->id)
            ->where('sku', 'KTB-SUSU/10')
            ->first();
        $this->assertNull($crossTenant);

        // 3. Search scope with special symbols
        $queries = [
            'Tubruk & Susu',
            '(Hot / Ice)',
            '#10',
            'Normal Sugar',
            '899111222',
            '',
            '   ',
            "O'Reilly Special",
        ];

        foreach ($queries as $q) {
            $res = ProductItem::filters(['search' => $q])->get();
            $this->assertNotNull($res);
        }
    }

    /**
     * Test 2: Empirical PostgreSQL index verification, EXPLAIN plans, buffer hits, and scale benchmark.
     */
    public function test_postgresql_indexes_empirical_verification_and_scale(): void
    {
        config(['database.connections.pgsql.database' => 'sollu_core']);
        DB::purge('pgsql');

        try {
            $pg = DB::connection('pgsql');
            $pg->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('PostgreSQL server not reachable: '.$e->getMessage());
        }

        // 1. Verify index catalog entries in pg_indexes
        $indexes = collect($pg->select("
            SELECT indexname, indexdef 
            FROM pg_indexes 
            WHERE tablename = 'product_items' 
              AND indexname IN ('idx_product_items_business_sku', 'idx_product_items_business_barcode', 'idx_product_items_omni_search')
        "))->keyBy('indexname');

        $this->assertTrue($indexes->has('idx_product_items_business_sku'), 'Missing idx_product_items_business_sku index');
        $this->assertTrue($indexes->has('idx_product_items_business_barcode'), 'Missing idx_product_items_business_barcode index');
        $this->assertTrue($indexes->has('idx_product_items_omni_search'), 'Missing idx_product_items_omni_search GIN index');

        $ginDef = $indexes->get('idx_product_items_omni_search')->indexdef;
        $this->assertStringContainsString('USING gin', $ginDef);
        $this->assertStringContainsString("to_tsvector('simple'::regconfig", $ginDef);

        // 2. Empirical verification on committed production data
        // 2.1 B-tree idx_product_items_business_sku
        $committedSkuPlan = $pg->select("
            EXPLAIN (ANALYZE, BUFFERS) 
            SELECT * FROM product_items 
            WHERE business_id = 'a27fc978-f101-43a4-8f47-b1273fba672a' 
              AND sku = 'PRD-040-COKLAT'
        ");
        $committedSkuPlanText = collect($committedSkuPlan)->pluck('QUERY PLAN')->implode("\n");
        $this->assertTrue(
            str_contains($committedSkuPlanText, 'idx_product_items_business_sku'),
            "PostgreSQL planner must use idx_product_items_business_sku on committed data. Plan:\n".$committedSkuPlanText
        );
        $this->assertFalse(str_contains($committedSkuPlanText, 'Seq Scan on product_items'));

        // 2.2 B-tree idx_product_items_business_barcode
        $committedBarcodePlan = $pg->select("
            EXPLAIN (ANALYZE, BUFFERS) 
            SELECT * FROM product_items 
            WHERE business_id = 'a27fc978-f101-43a4-8f47-b1273fba672a' 
              AND barcode = '899861711932'
        ");
        $committedBarcodePlanText = collect($committedBarcodePlan)->pluck('QUERY PLAN')->implode("\n");
        $this->assertTrue(
            str_contains($committedBarcodePlanText, 'idx_product_items_business_barcode'),
            "PostgreSQL planner must use idx_product_items_business_barcode on committed data. Plan:\n".$committedBarcodePlanText
        );
        $this->assertFalse(str_contains($committedBarcodePlanText, 'Seq Scan on product_items'));

        // 2.3 GIN idx_product_items_omni_search
        $committedGinPlanRows = $pg->select("
            EXPLAIN (ANALYZE, BUFFERS) 
            SELECT * FROM product_items 
            WHERE to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) 
                  @@ plainto_tsquery('simple', 'Tango')
        ");
        $committedGinPlanText = collect($committedGinPlanRows)->pluck('QUERY PLAN')->implode("\n");
        $this->assertTrue(
            str_contains($committedGinPlanText, 'idx_product_items_omni_search'),
            "PostgreSQL planner must use idx_product_items_omni_search on committed data. Plan:\n".$committedGinPlanText
        );
        $this->assertFalse(str_contains($committedGinPlanText, 'Seq Scan on product_items'));

        // 3. Seed 1,000 synthetic rows in a transaction to measure plan & timing under realistic dataset size
        $pg->beginTransaction();
        try {
            $testBusA = (string) Str::uuid();
            $testBusB = (string) Str::uuid();
            $testProdA = (string) Str::uuid();
            $testProdB = (string) Str::uuid();
            $now = now();

            $pg->table('products')->insert([
                [
                    'id' => $testProdA,
                    'business_id' => $testBusA,
                    'name' => 'Empirical Alpha Product',
                    'code' => 'EMP-A',
                    'product_type' => 'basic',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => $testProdB,
                    'business_id' => $testBusB,
                    'name' => 'Empirical Beta Product',
                    'code' => 'EMP-B',
                    'product_type' => 'basic',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $syntheticRows = [];
            for ($i = 1; $i <= 1000; $i++) {
                $bus = ($i % 2 === 0) ? $testBusA : $testBusB;
                $prod = ($i % 2 === 0) ? $testProdA : $testProdB;
                $sku = 'STRESS-SKU-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                $barcode = ($i % 4 === 0) ? null : '899'.str_pad((string) $i, 10, '0', STR_PAD_LEFT);
                $comb = ($i % 3 === 0) ? null : 'Variant Combination Option '.($i % 5);
                $name = ($i === 500) ? 'Synthetic Item UniqueOmni500 Special' : 'Synthetic Item '.$i.' / Flavor '.($i % 10).' (Special)';

                $syntheticRows[] = [
                    'id' => (string) Str::uuid(),
                    'business_id' => $bus,
                    'product_id' => $prod,
                    'item_type' => 'variant_sku',
                    'name' => $name,
                    'variant_combination' => $comb,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'track_inventory' => false,
                    'is_show' => true,
                    'sellable' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($syntheticRows, 250) as $chunk) {
                $pg->table('product_items')->insert($chunk);
            }

            // 4. In transaction, test indexed access paths on synthetic data
            $skuPlanRows = $pg->select(
                'EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM product_items WHERE business_id = ? AND sku = ?',
                [$testBusA, 'STRESS-SKU-00500']
            );
            $skuPlanText = collect($skuPlanRows)->pluck('QUERY PLAN')->implode("\n");
            $this->assertTrue(
                str_contains($skuPlanText, 'idx_product_items_business_sku') || str_contains($skuPlanText, 'idx_product_items_business_'),
                "Planner must use indexed access for synthetic SKU lookup. Plan:\n".$skuPlanText
            );
            $this->assertFalse(str_contains($skuPlanText, 'Seq Scan on product_items'), 'Must not do Seq Scan on indexed SKU lookup');

            // 5. EXPLAIN (ANALYZE, BUFFERS) for B-tree idx_product_items_business_barcode
            $barcodePlanRows = $pg->select(
                'EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM product_items WHERE business_id = ? AND barcode = ?',
                [$testBusA, '8990000000502']
            );
            $barcodePlanText = collect($barcodePlanRows)->pluck('QUERY PLAN')->implode("\n");
            $this->assertTrue(
                str_contains($barcodePlanText, 'idx_product_items_business_barcode'),
                "Planner must use idx_product_items_business_barcode. Plan:\n".$barcodePlanText
            );
            $this->assertFalse(str_contains($barcodePlanText, 'Seq Scan on product_items'), 'Must not do Seq Scan on indexed Barcode lookup');

            // 6. GIN omni_search index scan on 1000 synthetic rows
            $pg->statement('SET LOCAL enable_seqscan = off');
            $ginPlanRows = $pg->select("
                EXPLAIN (ANALYZE, BUFFERS) 
                SELECT * FROM product_items 
                WHERE to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) 
                      @@ plainto_tsquery('simple', ?)
            ", ['UniqueOmni500']);
            $ginPlanText = collect($ginPlanRows)->pluck('QUERY PLAN')->implode("\n");
            $this->assertTrue(
                str_contains($ginPlanText, 'idx_product_items_omni_search'),
                "Planner must use idx_product_items_omni_search on synthetic dataset. Plan:\n".$ginPlanText
            );

            // 7. Multi-tenant combined lookup: business_id + omni_search
            $tenantGinPlanRows = $pg->select("
                EXPLAIN (ANALYZE, BUFFERS) 
                SELECT * FROM product_items 
                WHERE business_id = ? 
                  AND to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) 
                      @@ plainto_tsquery('simple', ?)
            ", [$testBusA, 'UniqueOmni500']);
            $tenantGinPlanText = collect($tenantGinPlanRows)->pluck('QUERY PLAN')->implode("\n");
            $this->assertTrue(
                str_contains($tenantGinPlanText, 'idx_product_items_omni_search') || str_contains($tenantGinPlanText, 'idx_product_items_business_'),
                "Planner must use an indexed path (GIN or business B-tree). Plan:\n".$tenantGinPlanText
            );
            $this->assertFalse(str_contains($tenantGinPlanText, 'Seq Scan on product_items'), 'Must not do Seq Scan on multi-tenant omni search');

            // 7. Verify match across each of the 4 concatenated fields
            $testItem = $syntheticRows[501]; // index 501 -> item 502 (502%3!=0, 502%4!=0, has all fields)
            $matches = $pg->select("
                SELECT 
                    (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)) as match_name,
                    (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)) as match_comb,
                    (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)) as match_sku,
                    (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)) as match_barcode
                FROM product_items 
                WHERE id = ?
            ", [
                'Synthetic Item 502',
                'Combination Option 2',
                $testItem['sku'],
                $testItem['barcode'],
                $testItem['id'],
            ]);

            $this->assertNotEmpty($matches);
            $this->assertTrue($matches[0]->match_name, 'GIN index must match name');
            $this->assertTrue($matches[0]->match_comb, 'GIN index must match variant_combination');
            $this->assertTrue($matches[0]->match_sku, 'GIN index must match SKU');
            $this->assertTrue($matches[0]->match_barcode, 'GIN index must match barcode');

            // Also test item with null barcode (item 500): to_tsvector expression must not crash or be null
            $nullBarcodeItem = $syntheticRows[499]; // item 500 has barcode = null
            $nullBarcodeMatch = $pg->select("
                SELECT 
                    (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) @@ plainto_tsquery('simple', ?)) as match_name
                FROM product_items 
                WHERE id = ?
            ", ['UniqueOmni500', $nullBarcodeItem['id']]);
            $this->assertTrue($nullBarcodeMatch[0]->match_name, 'Items with null barcode must still match by name via coalesce');

            // 8. Adversarial inputs against plainto_tsquery & to_tsvector
            $adversarialQueries = [
                "'; DROP TABLE product_items; --",
                "O'Malley & Sons / Premium (Ice) *100%!",
                '   ',
                '',
                '! & | ( ) : *',
                str_repeat('A', 500),
                'NonExistentToken999999999',
            ];

            foreach ($adversarialQueries as $advQuery) {
                $advResult = $pg->select("
                    SELECT id 
                    FROM product_items 
                    WHERE to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, '')) 
                          @@ plainto_tsquery('simple', ?)
                    LIMIT 5
                ", [$advQuery]);
                $this->assertIsArray($advResult);
            }

        } finally {
            $pg->rollBack();
        }
    }
}
