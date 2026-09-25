<?php

namespace Tests\Feature\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

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
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::PRODUCT_CATALOG->value]]
        );

        $this->business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::PRODUCT_CATALOG->value],
            ],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Product Manager',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        // Grant product permissions
        foreach ([
            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::PRODUCT_CREATE->value,
            PermissionEnum::PRODUCT_UPDATE->value,
            PermissionEnum::PRODUCT_DELETE->value,
            PermissionEnum::PRODUCT_EXPORT->value,
            PermissionEnum::PRODUCT_IMPORT->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_guest_cannot_access_products(): void
    {
        $response = $this->get("http://{$this->appDomain}/master/products");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_products(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/master/products");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_products_page(): void
    {
        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Americano',
            'code' => 'PRD-001',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Kopi Americano')
            ->has('params')
            ->has('categories')
        );
    }

    public function test_user_can_create_service_product_without_inventory(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Jasa Potong Rambut',
                'code' => 'SRV-001',
                'product_type' => 'service',
                'base_price' => 45000,
                'track_inventory' => false,
                'has_variant' => false,
                'has_recipe' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'name' => 'Jasa Potong Rambut',
            'product_type' => 'service',
            'track_inventory' => false,
        ]);

        $this->assertDatabaseHas('product_prices', [
            'amount' => 45000,
        ]);
    }

    public function test_user_can_create_basic_product(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Nasi Goreng',
                'code' => 'PRD-NASI',
                'product_type' => 'basic',
                'base_price' => 25000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'name' => 'Nasi Goreng',
            'code' => 'PRD-NASI',
            'product_type' => 'basic',
        ]);
    }

    public function test_user_can_update_product(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Old Product Name',
            'code' => 'OLD-001',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'code' => 'NEW-001',
                'product_type' => 'basic',
                'base_price' => 30000,
            ]);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'code' => 'NEW-001',
        ]);
    }

    public function test_user_can_delete_product(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Product to Delete',
            'product_type' => 'basic',
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/master/products/{$product->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_products_index_supports_sorting_and_filtering_by_type(): void
    {
        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Produk A',
            'code' => 'A01',
            'product_type' => 'basic',
        ]);

        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Layanan B',
            'code' => 'B01',
            'product_type' => 'service',
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products?product_type=service");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Layanan B')
        );
    }

    public function test_form_options_returns_lookup_data_on_demand(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/products/form-options");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'outlets',
            'modifierGroups',
            'inventoryItems',
            'baseProducts',
            'uoms',
        ]);
    }

    public function test_tenant_isolation_cannot_access_other_business_products(): void
    {
        $otherType = BusinessType::first();
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $otherType->id,
        ]);

        $otherProduct = Product::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Secret Product',
            'product_type' => 'basic',
        ]);

        // Current user should not see other business's product in index
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products.data', 0)
        );

        // Current user cannot show other business's product
        $showResponse = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/products/{$otherProduct->id}");

        $showResponse->assertStatus(403);
    }

    public function test_products_index_scoped_by_outlet_and_filter(): void
    {
        $outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet A',
        ]);

        $outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet B',
        ]);

        $this->user->outlets()->sync([$outletA->id, $outletB->id]);

        $productA = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Product Only In A',
            'product_type' => 'basic',
        ]);
        $productA->outlets()->sync([
            $outletA->id => ['is_enabled' => true, 'is_available' => true],
            $outletB->id => ['is_enabled' => false, 'is_available' => true],
        ]);

        $productB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Product In Both',
            'product_type' => 'basic',
        ]);
        $productB->outlets()->sync([
            $outletA->id => ['is_enabled' => true, 'is_available' => true],
            $outletB->id => ['is_enabled' => true, 'is_available' => true],
        ]);

        // Filter by Outlet B explicitly
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products?outlet={$outletB->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Product In Both')
        );

        // Filter by Outlet A explicitly
        $responseA = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products?outlet={$outletA->id}");

        $responseA->assertStatus(200);
        $responseA->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products.data', 2)
        );
    }

    public function test_user_can_create_single_basic_product_with_inventory_tracking(): void
    {
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_active' => true,
        ]);

        $uom = Uom::first();

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Kopi Arabika 250g',
                'code' => 'KOP-ARB-250',
                'barcode' => '899123456789',
                'product_type' => 'basic',
                'base_price' => 75000,
                'has_variant' => false,
                'track_inventory' => true,
                'uom_id' => $uom->id,
                'is_show' => true,
                'sellable' => true,
                'outlets' => [
                    [
                        'outlet_id' => $outlet->id,
                        'is_enabled' => true,
                        'is_available' => true,
                    ],
                ],
            ]);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $product = Product::where('code', 'KOP-ARB-250')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->track_inventory);

        // Verify ProductItem created with uom_id
        $this->assertDatabaseHas('product_items', [
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Kopi Arabika 250g',
            'sku' => 'KOP-ARB-250',
            'barcode' => '899123456789',
            'uom_id' => $uom->id,
            'track_inventory' => true,
        ]);

        $prodItem = ProductItem::where('product_id', $product->id)->first();

        // Verify InventoryItem snapshot created with name and uom_id (default minimum_stock = 0)
        $this->assertDatabaseHas('inventory_items', [
            'business_id' => $this->business->id,
            'product_item_id' => $prodItem->id,
            'name' => 'Kopi Arabika 250g',
            'uom_id' => $uom->id,
            'minimum_stock' => 0,
        ]);

        $invItem = InventoryItem::where('product_item_id', $prodItem->id)->first();

        // Verify InventoryBalance initialized
        $this->assertDatabaseHas('inventory_balances', [
            'business_id' => $this->business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 0,
        ]);

        // Verify Price attached to product_item_id
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_item_id' => $prodItem->id,
            'amount' => 75000,
        ]);
    }

    public function test_user_can_create_variant_product_with_inventory_tracking(): void
    {
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Store',
            'is_active' => true,
        ]);

        $uom = Uom::first();

        $payload = [
            'name' => 'Baju Merch Naruto',
            'code' => 'TEE-NARUTO',
            'product_type' => 'basic',
            'base_price' => 299000,
            'has_variant' => true,
            'track_inventory' => true,
            'uom_id' => $uom->id,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                [
                    'outlet_id' => $outlet->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ],
            ],
            'variants' => [
                [
                    'name' => 'Warna',
                    'options' => [
                        ['name' => 'White'],
                        ['name' => 'Black'],
                    ],
                ],
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'S'],
                        ['name' => 'M'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Warna' => 'White', 'Ukuran' => 'S'],
                    'sku' => 'NARUTO-W-S',
                    'barcode' => '899001',
                    'price' => 299000,
                ],
                [
                    'options' => ['Warna' => 'White', 'Ukuran' => 'M'],
                    'sku' => 'NARUTO-W-M',
                    'barcode' => '899002',
                    'price' => 299000,
                ],
                [
                    'options' => ['Warna' => 'Black', 'Ukuran' => 'S'],
                    'sku' => 'NARUTO-B-S',
                    'barcode' => '899003',
                    'price' => 319000,
                ],
                [
                    'options' => ['Warna' => 'Black', 'Ukuran' => 'M'],
                    'sku' => 'NARUTO-B-M',
                    'barcode' => '899004',
                    'price' => 319000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $product = Product::where('code', 'TEE-NARUTO')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->has_variant);
        $this->assertTrue($product->track_inventory);

        // Verify Variant Groups and Options
        $this->assertDatabaseHas('variant_groups', ['product_id' => $product->id, 'name' => 'Warna']);
        $this->assertDatabaseHas('variant_groups', ['product_id' => $product->id, 'name' => 'Ukuran']);

        // Verify 4 ProductItems created with uom_id and variant_combination
        $this->assertEquals(4, ProductItem::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('product_items', [
            'product_id' => $product->id,
            'name' => 'Baju Merch Naruto - White - S',
            'sku' => 'NARUTO-W-S',
            'uom_id' => $uom->id,
            'track_inventory' => true,
        ]);

        // Verify 4 InventoryItems snapshot created (default minimum_stock = 0)
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Baju Merch Naruto - White - S',
            'uom_id' => $uom->id,
            'minimum_stock' => 0,
        ]);

        // Verify InventoryBalances created for enabled outlet
        $prodItem = ProductItem::where('sku', 'NARUTO-W-S')->first();
        $this->assertDatabaseHas('inventory_balances', [
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $prodItem->inventoryItem->id,
            'current_stock' => 0,
        ]);

        // Verify variant prices
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_item_id' => $prodItem->id,
            'amount' => 299000,
        ]);
    }

    public function test_user_can_create_variant_product_without_inventory_tracking(): void
    {
        $payload = [
            'name' => 'Kaos Event Custom',
            'code' => 'EVT-001',
            'product_type' => 'basic',
            'base_price' => 100000,
            'has_variant' => true,
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
            'variants' => [
                [
                    'name' => 'Size',
                    'options' => [
                        ['name' => 'S'],
                        ['name' => 'M'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Size' => 'S'],
                    'sku' => 'EVT-S',
                    'price' => 100000,
                ],
                [
                    'options' => ['Size' => 'M'],
                    'sku' => 'EVT-M',
                    'price' => 100000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));

        $product = Product::where('code', 'EVT-001')->first();
        $this->assertNotNull($product);
        $this->assertFalse($product->track_inventory);
        $this->assertEquals(2, ProductItem::where('product_id', $product->id)->count());

        // No inventory balances initialized
        $this->assertDatabaseMissing('inventory_balances', [
            'business_id' => $this->business->id,
        ]);
    }

    public function test_user_can_update_single_product_and_sync_inventory_snapshot(): void
    {
        $uom1 = Uom::first();
        $uom2 = Uom::skip(1)->first();

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Original Coffee',
            'code' => 'COF-01',
            'product_type' => 'basic',
            'has_variant' => false,
            'track_inventory' => true,
        ]);

        $prodItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'uom_id' => $uom1->id,
            'item_type' => 'variant_sku',
            'name' => 'Original Coffee',
            'sku' => 'COF-01',
            'track_inventory' => true,
        ]);

        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $prodItem->id,
            'name' => 'Original Coffee',
            'uom_id' => $uom1->id,
            'minimum_stock' => 5,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", [
                'name' => 'Premium Specialty Coffee',
                'code' => 'COF-PREM-01',
                'product_type' => 'basic',
                'base_price' => 95000,
                'has_variant' => false,
                'track_inventory' => true,
                'uom_id' => $uom2->id,
            ]);

        $response->assertRedirect(route('master.products.index'));

        // Verify ProductItem updated
        $this->assertDatabaseHas('product_items', [
            'id' => $prodItem->id,
            'name' => 'Premium Specialty Coffee',
            'sku' => 'COF-PREM-01',
            'uom_id' => $uom2->id,
        ]);

        // Verify InventoryItem snapshot updated and existing minimum_stock (5) preserved
        $this->assertDatabaseHas('inventory_items', [
            'id' => $invItem->id,
            'name' => 'Premium Specialty Coffee',
            'uom_id' => $uom2->id,
            'minimum_stock' => 5,
        ]);
    }

    public function test_user_can_update_variant_product_and_sync_inventory_snapshots(): void
    {
        $uom1 = Uom::first();
        $uom2 = Uom::skip(1)->first();

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Sepatu Olahraga',
            'code' => 'SPT-001',
            'product_type' => 'basic',
            'has_variant' => true,
            'track_inventory' => true,
        ]);

        $vg = $product->variantGroups()->create(['name' => 'Ukuran', 'sort_order' => 0]);
        $opt39 = $vg->options()->create(['name' => '39', 'sort_order' => 0]);
        $opt40 = $vg->options()->create(['name' => '40', 'sort_order' => 1]);

        $item39 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'uom_id' => $uom1->id,
            'item_type' => 'variant_sku',
            'name' => 'Sepatu Olahraga - 39',
            'sku' => 'SPT-39',
            'track_inventory' => true,
        ]);
        $inv39 = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $item39->id,
            'name' => 'Sepatu Olahraga - 39',
            'uom_id' => $uom1->id,
            'minimum_stock' => 10,
        ]);

        $item40 = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'uom_id' => $uom1->id,
            'item_type' => 'variant_sku',
            'name' => 'Sepatu Olahraga - 40',
            'sku' => 'SPT-40',
            'track_inventory' => true,
        ]);
        $inv40 = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $item40->id,
            'name' => 'Sepatu Olahraga - 40',
            'uom_id' => $uom1->id,
            'minimum_stock' => 10,
        ]);

        // Update product name to 'Sepatu Lari Marathon' and change UOM to $uom2
        $payload = [
            'name' => 'Sepatu Lari Marathon',
            'code' => 'SPT-RUN',
            'product_type' => 'basic',
            'base_price' => 500000,
            'has_variant' => true,
            'track_inventory' => true,
            'uom_id' => $uom2->id,
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => '39'],
                        ['name' => '40'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => '39'],
                    'sku' => 'SPT-39',
                    'price' => 520000,
                ],
                [
                    'options' => ['Ukuran' => '40'],
                    'sku' => 'SPT-40',
                    'price' => 520000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $payload);

        $response->assertRedirect(route('master.products.index'));

        // Verify both ProductItems updated with new product name prefix and uom
        $this->assertDatabaseHas('product_items', [
            'id' => $item39->id,
            'name' => 'Sepatu Lari Marathon - 39',
            'uom_id' => $uom2->id,
        ]);
        $this->assertDatabaseHas('product_items', [
            'id' => $item40->id,
            'name' => 'Sepatu Lari Marathon - 40',
            'uom_id' => $uom2->id,
        ]);

        // Verify both InventoryItems snapshots updated and minimum_stock preserved
        $this->assertDatabaseHas('inventory_items', [
            'id' => $inv39->id,
            'name' => 'Sepatu Lari Marathon - 39',
            'uom_id' => $uom2->id,
            'minimum_stock' => 10,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $inv40->id,
            'name' => 'Sepatu Lari Marathon - 40',
            'uom_id' => $uom2->id,
            'minimum_stock' => 10,
        ]);
    }

    public function test_user_can_toggle_track_inventory_on_product(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Non-Tracked Snack',
            'product_type' => 'basic',
            'has_variant' => false,
            'track_inventory' => false,
        ]);

        $uom = Uom::first();

        // 1. Enable inventory tracking
        $response1 = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", [
                'name' => 'Non-Tracked Snack',
                'product_type' => 'basic',
                'base_price' => 15000,
                'has_variant' => false,
                'track_inventory' => true,
                'uom_id' => $uom->id,
            ]);

        $response1->assertRedirect(route('master.products.index'));
        $this->assertTrue($product->fresh()->track_inventory);
        $this->assertDatabaseHas('product_items', [
            'product_id' => $product->id,
            'track_inventory' => true,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Non-Tracked Snack',
            'uom_id' => $uom->id,
            'is_active' => true,
        ]);

        // 2. Disable inventory tracking
        $response2 = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", [
                'name' => 'Non-Tracked Snack',
                'product_type' => 'basic',
                'base_price' => 15000,
                'has_variant' => false,
                'track_inventory' => false,
            ]);

        $response2->assertRedirect(route('master.products.index'));
        $this->assertFalse($product->fresh()->track_inventory);
        $this->assertDatabaseHas('product_items', [
            'product_id' => $product->id,
            'track_inventory' => false,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Non-Tracked Snack',
            'is_active' => false,
        ]);
    }

    public function test_user_can_update_product_with_twelve_variant_combinations_successfully(): void
    {
        $uom = Uom::first();

        // 1. Create product with 3 colors x 4 sizes = 12 combinations
        $colors = ['Merah', 'Biru', 'Hijau'];
        $sizes = ['S', 'M', 'L', 'XL'];

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kaos Polos Premium',
            'code' => 'KOS-PL-01',
            'product_type' => 'basic',
            'has_variant' => true,
            'track_inventory' => true,
        ]);

        $vgWarna = $product->variantGroups()->create(['name' => 'Warna', 'sort_order' => 0]);
        foreach ($colors as $idx => $color) {
            $vgWarna->options()->create(['name' => $color, 'sort_order' => $idx]);
        }

        $vgUkuran = $product->variantGroups()->create(['name' => 'Ukuran', 'sort_order' => 1]);
        foreach ($sizes as $idx => $size) {
            $vgUkuran->options()->create(['name' => $size, 'sort_order' => $idx]);
        }

        $combinations = [];
        foreach ($colors as $c) {
            foreach ($sizes as $s) {
                $item = ProductItem::create([
                    'business_id' => $this->business->id,
                    'product_id' => $product->id,
                    'uom_id' => $uom->id,
                    'item_type' => 'variant_sku',
                    'name' => "Kaos Polos Premium - {$c} - {$s}",
                    'sku' => "KOS-{$c}-{$s}",
                    'track_inventory' => true,
                ]);

                InventoryItem::create([
                    'business_id' => $this->business->id,
                    'product_item_id' => $item->id,
                    'name' => "Kaos Polos Premium - {$c} - {$s}",
                    'uom_id' => $uom->id,
                    'minimum_stock' => 0,
                ]);

                $combinations[] = [
                    'options' => ['Warna' => $c, 'Ukuran' => $s],
                    'sku' => "KOS-{$c}-{$s}",
                    'barcode' => '899'.rand(1000, 9999),
                    'price' => 75000,
                ];
            }
        }

        $this->assertCount(12, $combinations);

        // 2. Perform update with all 12 combinations
        $payload = [
            'name' => 'Kaos Polos Premium Cotton Combed',
            'code' => 'KOS-PL-COMBED',
            'product_type' => 'basic',
            'base_price' => 80000,
            'has_variant' => true,
            'track_inventory' => true,
            'uom_id' => $uom->id,
            'variants' => [
                [
                    'name' => 'Warna',
                    'options' => array_map(fn ($c) => ['name' => $c], $colors),
                ],
                [
                    'name' => 'Ukuran',
                    'options' => array_map(fn ($s) => ['name' => $s], $sizes),
                ],
            ],
            'variant_combinations' => $combinations,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $payload);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertEquals(12, ProductItem::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Kaos Polos Premium Cotton Combed',
        ]);
        $this->assertDatabaseHas('product_items', [
            'product_id' => $product->id,
            'name' => 'Kaos Polos Premium Cotton Combed - Merah - S',
            'uom_id' => $uom->id,
        ]);
    }

    public function test_user_can_create_and_update_variant_product_with_granular_item_flags(): void
    {
        $uom = Uom::first();

        // 1. Create product with granular flags
        $createPayload = [
            'name' => 'Jaket Bomber Granular',
            'code' => 'JKT-BMB',
            'product_type' => 'basic',
            'base_price' => 250000,
            'has_variant' => true,
            'track_inventory' => true,
            'uom_id' => $uom->id,
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'S'],
                        ['name' => 'M'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'S'],
                    'sku' => 'JKT-BMB-S',
                    'price' => 250000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'JKT-BMB-M',
                    'price' => 260000,
                    'track_inventory' => false,
                    'sellable' => false,
                    'is_active' => false,
                ],
            ],
        ];

        $response1 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $createPayload);

        $response1->assertRedirect(route('master.products.index'));

        $product = Product::where('code', 'JKT-BMB')->first();
        $this->assertNotNull($product);

        $itemS = ProductItem::where('sku', 'JKT-BMB-S')->first();
        $this->assertTrue($itemS->track_inventory);
        $this->assertTrue($itemS->sellable);
        $this->assertTrue($itemS->is_active);
        $this->assertDatabaseHas('inventory_items', [
            'product_item_id' => $itemS->id,
            'is_active' => true,
        ]);

        $itemM = ProductItem::where('sku', 'JKT-BMB-M')->first();
        $this->assertFalse($itemM->track_inventory);
        $this->assertFalse($itemM->sellable);
        $this->assertFalse($itemM->is_active);

        // 2. Update product: toggle itemM to active and itemS to not sellable
        $updatePayload = [
            'name' => 'Jaket Bomber Granular Updated',
            'code' => 'JKT-BMB',
            'product_type' => 'basic',
            'base_price' => 270000,
            'has_variant' => true,
            'track_inventory' => true,
            'uom_id' => $uom->id,
            'variants' => $createPayload['variants'],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'S'],
                    'sku' => 'JKT-BMB-S',
                    'price' => 270000,
                    'track_inventory' => true,
                    'sellable' => false,
                    'is_active' => true,
                ],
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'JKT-BMB-M',
                    'price' => 280000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                ],
            ],
        ];

        $response2 = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);

        $response2->assertRedirect(route('master.products.index'));

        $itemSUpdated = ProductItem::where('sku', 'JKT-BMB-S')->first();
        $this->assertFalse($itemSUpdated->sellable);
        $this->assertTrue($itemSUpdated->is_active);

        $itemMUpdated = ProductItem::where('sku', 'JKT-BMB-M')->first();
        $this->assertTrue($itemMUpdated->track_inventory);
        $this->assertTrue($itemMUpdated->sellable);
        $this->assertTrue($itemMUpdated->is_active);
        $this->assertDatabaseHas('inventory_items', [
            'product_item_id' => $itemMUpdated->id,
            'is_active' => true,
        ]);
    }
}
