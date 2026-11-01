<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\AuditVariantIntegrity;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Tests\TestCase;

class AuditVariantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_audit_variant_integrity_passes_on_clean_data(): void
    {
        $tool = new AuditVariantIntegrity;
        $request = new Request([]);

        $response = $tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        $this->assertSame('pass', $data['status']);
        $this->assertSame(0, $data['total_violations']);
        $this->assertEmpty($data['violations']);
    }

    public function test_audit_variant_integrity_detects_duplicate_group_options(): void
    {
        $type = BusinessType::first() ?? BusinessType::create([
            'name' => 'Retail',
            'code' => 'retail',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Audit Merchant',
            'owner_name' => 'Owner',
            'email' => 'audit_merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        // Setup product with variant group and 2 options
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Kemeja Flannel',
            'code' => 'KMJ-001',
            'product_type' => 'basic',
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
        ]);

        $group = VariantGroup::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'name' => 'Ukuran',
        ]);

        $optionS = VariantGroupOption::create([
            'business_id' => $business->id,
            'variant_group_id' => $group->id,
            'name' => 'S',
        ]);

        $optionM = VariantGroupOption::create([
            'business_id' => $business->id,
            'variant_group_id' => $group->id,
            'name' => 'M',
        ]);

        $item = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'sku' => 'KMJ-S',
            'item_type' => 'variant_sku',
            'price' => 125000,
            'variant_combination' => 'S',
        ]);

        // Attach both options to the SAME product item (contamination)
        DB::table('product_item_variant_group_options')->insert([
            ['product_item_id' => $item->id, 'variant_group_option_id' => $optionS->id],
            ['product_item_id' => $item->id, 'variant_group_option_id' => $optionM->id],
        ]);

        $tool = new AuditVariantIntegrity;
        $request = new Request([]);

        $response = $tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        $this->assertSame('error', $data['status']);
        $this->assertGreaterThan(0, $data['total_violations']);
        $this->assertNotEmpty($data['violations']['duplicate_group_options']);

        $violation = $data['violations']['duplicate_group_options'][0];
        $this->assertSame($item->id, $violation['product_item_id']);
        $this->assertSame('Ukuran', $violation['variant_group']);
        $this->assertSame(2, $violation['total_attached']);
    }
}
