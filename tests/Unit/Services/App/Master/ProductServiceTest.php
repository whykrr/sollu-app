<?php

namespace Tests\Unit\Services\App\Master;

use App\Enums\ProductTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Master\AuditLogService;
use App\Services\App\Master\InventoryService;
use App\Services\App\Master\ProductService;
use App\Services\App\Master\RecipeService;
use App\Services\Core\ImageOptimizerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogServiceMock;

    protected InventoryService $inventoryServiceMock;

    protected RecipeService $recipeServiceMock;

    protected ImageOptimizerService $imageOptimizerServiceMock;

    protected ProductService $service;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Manager',
            'email' => 'manager_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $this->auditLogServiceMock = Mockery::mock(AuditLogService::class);
        $this->auditLogServiceMock->shouldReceive('log')->byDefault()->andReturnNull();

        $this->inventoryServiceMock = Mockery::mock(InventoryService::class);
        $this->inventoryServiceMock->shouldReceive('syncInventoryBalances')->byDefault()->andReturnNull();

        $this->recipeServiceMock = Mockery::mock(RecipeService::class);

        $this->imageOptimizerServiceMock = Mockery::mock(ImageOptimizerService::class);
        $this->imageOptimizerServiceMock->shouldReceive('delete')->byDefault()->andReturnTrue();

        $this->service = new ProductService(
            $this->auditLogServiceMock,
            $this->inventoryServiceMock,
            $this->recipeServiceMock,
            $this->imageOptimizerServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_service_product_forcing_disabled_inventory_and_variants(): void
    {
        $this->inventoryServiceMock->shouldNotReceive('linkInventoryItem');

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Jasa Servis Komputer',
            'product_type' => 'service',
            'base_price' => 75000,
            'has_variant' => true, // should be forced to false
            'track_inventory' => true, // should be forced to false
        ];

        $product = $this->service->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Jasa Servis Komputer', $product->name);
        $this->assertEquals(ProductTypeEnum::SERVICE, $product->product_type);
        $this->assertFalse($product->track_inventory);
        $this->assertFalse($product->has_variant);
        $this->assertCount(1, $product->prices);
        $this->assertEquals(75000, $product->prices->first()->amount);
        $this->assertNull($product->prices->first()->product_item_id);
    }

    public function test_it_creates_basic_product_with_inventory_tracking(): void
    {
        $invItem = new InventoryItem(['id' => Str::uuid()->toString()]);

        $this->inventoryServiceMock->shouldReceive('linkInventoryItem')
            ->once()
            ->with(Mockery::type(ProductItem::class), Mockery::type('array'), Mockery::any())
            ->andReturn($invItem);

        $data = [
            'business_id' => $this->business->id,
            'code' => 'PRD-001',
            'name' => 'Susu Kotak',
            'product_type' => 'basic',
            'base_price' => 12000,
            'has_variant' => false,
            'track_inventory' => true,
        ];

        $product = $this->service->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Susu Kotak', $product->name);
        $this->assertEquals('PRD-001', $product->code);
        $this->assertTrue($product->track_inventory);
        $this->assertCount(1, $product->prices);
        $this->assertEquals(12000, $product->prices->first()->amount);
        $this->assertNotNull($product->prices->first()->product_item_id);
    }

    public function test_it_creates_basic_product_without_inventory_tracking(): void
    {
        $this->inventoryServiceMock->shouldNotReceive('linkInventoryItem');

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Stiker Toko',
            'product_type' => 'basic',
            'base_price' => 2000,
            'has_variant' => false,
            'track_inventory' => false,
        ];

        $product = $this->service->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertFalse($product->track_inventory);
        $this->assertCount(1, $product->prices);
        $this->assertEquals(2000, $product->prices->first()->amount);
        $this->assertNull($product->prices->first()->product_item_id);
    }

    public function test_it_creates_bundle_product_with_components(): void
    {
        $comp1 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Bubuk',
            'product_type' => 'basic',
        ]);
        $comp2 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Gula Pasir',
            'product_type' => 'basic',
        ]);

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Paket Ngopi Hemat',
            'product_type' => 'bundle',
            'base_price' => 45000,
            'bundle_items' => [
                ['component_product_id' => $comp1->id, 'qty' => 1],
                ['component_product_id' => $comp2->id, 'qty' => 2],
            ],
        ];

        $product = $this->service->createProduct($data);

        $this->assertEquals(ProductTypeEnum::BUNDLE, $product->product_type);
        $this->assertFalse($product->track_inventory);
        $this->assertCount(2, $product->bundleItems);
        $this->assertEquals($comp1->id, $product->bundleItems[0]->component_product_id);
        $this->assertEquals(1, $product->bundleItems[0]->qty);
        $this->assertEquals($comp2->id, $product->bundleItems[1]->component_product_id);
        $this->assertEquals(2, $product->bundleItems[1]->qty);
    }

    public function test_it_creates_product_with_variants_and_variant_combinations(): void
    {
        $invItem = new InventoryItem(['id' => Str::uuid()->toString()]);

        $this->inventoryServiceMock->shouldReceive('linkInventoryItem')
            ->twice()
            ->with(Mockery::type(ProductItem::class), Mockery::any(), Mockery::any())
            ->andReturn($invItem);

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Kemeja Formal',
            'product_type' => 'basic',
            'base_price' => 150000,
            'has_variant' => true,
            'track_inventory' => true,
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'M'],
                        ['name' => 'L'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'KMJ-M',
                    'price' => 150000,
                ],
                [
                    'options' => ['Ukuran' => 'L'],
                    'sku' => 'KMJ-L',
                    'price' => 160000,
                ],
            ],
        ];

        $product = $this->service->createProduct($data);

        $this->assertTrue($product->has_variant);
        $this->assertCount(1, $product->variantGroups);
        $this->assertCount(2, $product->variantGroups->first()->options);
        $this->assertCount(3, $product->prices); // 1 base price + 2 variant prices
    }

    public function test_it_creates_and_updates_variant_product_with_granular_item_flags(): void
    {
        $invItem = new InventoryItem(['id' => Str::uuid()->toString()]);

        // When creating: only 1 variant has track_inventory = true & is_active = true
        $this->inventoryServiceMock->shouldReceive('linkInventoryItem')
            ->once()
            ->with(Mockery::type(ProductItem::class), Mockery::any(), Mockery::any())
            ->andReturn($invItem);

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Jaket Kulit',
            'product_type' => 'basic',
            'base_price' => 500000,
            'has_variant' => true,
            'track_inventory' => true,
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'M'],
                        ['name' => 'L'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'JKT-M',
                    'price' => 500000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Ukuran' => 'L'],
                    'sku' => 'JKT-L',
                    'price' => 500000,
                    'track_inventory' => false,
                    'sellable' => false,
                    'is_active' => false,
                ],
            ],
        ];

        $product = $this->service->createProduct($data);

        $itemM = ProductItem::where('sku', 'JKT-M')->first();
        $this->assertNotNull($itemM);
        $this->assertTrue($itemM->track_inventory);
        $this->assertTrue($itemM->sellable);
        $this->assertTrue($itemM->is_active);

        $itemL = ProductItem::where('sku', 'JKT-L')->first();
        $this->assertNotNull($itemL);
        $this->assertFalse($itemL->track_inventory);
        $this->assertFalse($itemL->sellable);
        $this->assertFalse($itemL->is_active);

        // Now update: enable itemL and make it tracked & sellable
        $this->inventoryServiceMock->shouldReceive('linkInventoryItem')
            ->twice()
            ->with(Mockery::type(ProductItem::class), Mockery::any(), Mockery::any())
            ->andReturn($invItem);

        $updateData = [
            'name' => 'Jaket Kulit Asli',
            'base_price' => 550000,
            'has_variant' => true,
            'track_inventory' => true,
            'variants' => $data['variants'],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'JKT-M',
                    'price' => 550000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Ukuran' => 'L'],
                    'sku' => 'JKT-L',
                    'price' => 550000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
            ],
        ];

        $this->service->updateProduct($product, $updateData);

        $updatedL = ProductItem::where('sku', 'JKT-L')->first();
        $this->assertTrue($updatedL->track_inventory);
        $this->assertTrue($updatedL->sellable);
        $this->assertTrue($updatedL->is_active);
    }

    public function test_it_creates_product_with_multiple_images_and_optimizes_files(): void
    {
        $fakeFile = UploadedFile::fake()->image('product.jpg');

        $this->imageOptimizerServiceMock->shouldReceive('optimizeAndStore')
            ->once()
            ->with($fakeFile, 'products', 'product')
            ->andReturn('products/optimized_product.webp');

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Kaos Polos',
            'product_type' => 'basic',
            'base_price' => 50000,
            'has_variant' => false,
            'track_inventory' => false,
            'images' => [
                [
                    'image_file' => $fakeFile,
                    'sort_order' => 0,
                ],
                [
                    'image_url' => '/storage/products/existing_image.webp',
                    'sort_order' => 1,
                ],
            ],
        ];

        $product = $this->service->createProduct($data);

        $this->assertEquals('products/optimized_product.webp', $product->image_url);
        $this->assertCount(2, $product->images);
        $this->assertEquals('products/optimized_product.webp', $product->images[0]->image_url);
        $this->assertEquals('products/existing_image.webp', $product->images[1]->image_url);
    }

    public function test_it_updates_basic_product_and_syncs_inventory_balances(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Old Product',
            'product_type' => 'basic',
            'has_variant' => false,
            'track_inventory' => true,
        ]);

        $prodItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Old Product',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
        ]);

        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $prodItem->id,
            'minimum_stock' => 0,
        ]);

        $this->inventoryServiceMock->shouldReceive('linkInventoryItem')
            ->once()
            ->with(Mockery::type(ProductItem::class), Mockery::any(), Mockery::any())
            ->andReturn($invItem);

        $updateData = [
            'name' => 'Updated Product',
            'base_price' => 25000,
            'track_inventory' => true,
        ];

        $updated = $this->service->updateProduct($product, $updateData);

        $this->assertEquals('Updated Product', $updated->name);
        $this->assertCount(1, $updated->prices);
        $this->assertEquals(25000, $updated->prices->first()->amount);
    }

    public function test_it_updates_product_prices_and_outlet_specific_prices(): void
    {
        $otherOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Branch Outlet',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Es Teh Manis',
            'product_type' => 'basic',
            'has_variant' => false,
            'track_inventory' => false,
        ]);

        $product->prices()->create([
            'outlet_id' => null,
            'amount' => 5000,
        ]);

        $updateData = [
            'base_price' => 6000,
            'outlet_prices' => [
                [
                    'outlet_id' => $otherOutlet->id,
                    'amount' => 7000,
                ],
            ],
        ];

        $updated = $this->service->updateProduct($product, $updateData);

        $this->assertCount(2, $updated->prices);
        $this->assertEquals(6000, $updated->prices->firstWhere('outlet_id', null)->amount);
        $this->assertEquals(7000, $updated->prices->firstWhere('outlet_id', $otherOutlet->id)->amount);
    }

    public function test_it_deletes_orphaned_images_on_update(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Foto Produk',
            'product_type' => 'basic',
            'image_url' => 'products/old_cover.webp',
        ]);

        $product->images()->create([
            'image_url' => 'products/old_cover.webp',
            'sort_order' => 0,
        ]);
        $product->images()->create([
            'image_url' => 'products/to_delete.webp',
            'sort_order' => 1,
        ]);

        // to_delete.webp should be removed via ImageOptimizerService
        $this->imageOptimizerServiceMock->shouldReceive('delete')
            ->once()
            ->with('products/to_delete.webp')
            ->andReturnTrue();

        $updateData = [
            'images' => [
                [
                    'image_url' => 'products/old_cover.webp',
                    'sort_order' => 0,
                ],
            ],
        ];

        $this->service->updateProduct($product, $updateData);

        $this->assertCount(1, $product->fresh()->images);
        $this->assertEquals('products/old_cover.webp', $product->fresh()->images->first()->image_url);
    }

    public function test_it_always_logs_audit_trail_on_create_and_update(): void
    {
        $this->auditLogServiceMock->shouldReceive('log')
            ->once()
            ->with($this->business->id, 'product', Mockery::type('string'), 'created', null, Mockery::type('array'))
            ->andReturnNull();

        $this->auditLogServiceMock->shouldReceive('log')
            ->once()
            ->with($this->business->id, 'product', Mockery::type('string'), 'updated', Mockery::type('array'), Mockery::type('array'))
            ->andReturnNull();

        $product = $this->service->createProduct([
            'business_id' => $this->business->id,
            'name' => 'Audit Test Product',
            'product_type' => 'basic',
            'base_price' => 10000,
            'track_inventory' => false,
        ]);

        $this->assertInstanceOf(Product::class, $product);

        $updated = $this->service->updateProduct($product, [
            'name' => 'Audit Test Product Renamed',
        ]);

        $this->assertEquals('Audit Test Product Renamed', $updated->name);
    }
}
