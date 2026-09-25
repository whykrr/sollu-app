<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\RecipeVersion;
use App\Services\App\Master\AuditLogService;
use App\Services\App\Master\RecipeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RecipeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogServiceMock;

    protected RecipeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogServiceMock = Mockery::mock(AuditLogService::class);
        $this->auditLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new RecipeService($this->auditLogServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createTenant(): Business
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        return Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
    }

    public function test_it_syncs_recipe_and_creates_new_version()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Coffee',
            'product_type' => 'basic',
            'has_recipe' => true,
        ]);

        $invItem = ProductItem::create([
            'business_id' => $business->id,
            'name' => 'Coffee Beans',
            'item_type' => 'raw_material',
        ]);

        // First sync
        $items = [
            [
                'product_item_id' => $invItem->id,
                'qty' => 15,
                'uom' => 'gr',
            ],
        ];

        $recipe1 = $this->service->syncRecipe($product, $items);

        $this->assertInstanceOf(RecipeVersion::class, $recipe1);
        $this->assertEquals(1, $recipe1->version_number);
        $this->assertTrue($recipe1->is_active);
        $this->assertCount(1, $recipe1->items);

        // Second sync updates version
        $items2 = [
            [
                'product_item_id' => $invItem->id,
                'qty' => 18, // changed qty
                'uom' => 'gr',
            ],
        ];

        $recipe2 = $this->service->syncRecipe($product, $items2);

        $this->assertEquals(2, $recipe2->version_number);
        $this->assertTrue($recipe2->is_active);
        $this->assertFalse($recipe1->fresh()->is_active); // old version deactivated

        $this->assertDatabaseHas('product_recipe_items', [
            'recipe_version_id' => $recipe2->id,
            'qty' => 18,
        ]);
    }
}
