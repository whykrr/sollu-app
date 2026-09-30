<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductItemSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Outlet $secondaryOutlet;

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
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVOICE_DEBT->value,
                    FeatureEnum::PRODUCT_CATALOG->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Product Search Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'prod_search_'.uniqid().'@test.com',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVOICE_DEBT->value,
                    FeatureEnum::PRODUCT_CATALOG->value,
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_active' => true,
        ]);

        $this->secondaryOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Sales Staff',
            'email' => 'staff_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->attach([$this->outlet->id, $this->secondaryOutlet->id]);

        setPermissionsTeamId($this->business->id);
        Permission::findOrCreate(PermissionEnum::PRODUCT_VIEW->value, 'business');
        $this->user->givePermissionTo(PermissionEnum::PRODUCT_VIEW->value);

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();
    }

    public function test_search_items_returns_single_product_without_variants(): void
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman',
            'slug' => 'minuman',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'product_category_id' => $category->id,
            'name' => 'Air Mineral 600ml',
            'code' => 'MIN-600',
            'product_type' => ProductTypeEnum::BASIC,
            'has_variant' => false,
            'is_show' => true,
            'sellable' => true,
        ]);

        $product->prices()->create([
            'outlet_id' => null,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items?search=Mineral");

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($product->id, $data[0]['id']);
        $this->assertEquals($product->id, $data[0]['product_id']);
        $this->assertNull($data[0]['product_item_id']);
        $this->assertEquals('Air Mineral 600ml', $data[0]['name']);
        $this->assertEquals('MIN-600', $data[0]['sku']);
        $this->assertEquals(5000, $data[0]['price']);
        $this->assertEquals('basic', $data[0]['product_type']);
        $this->assertEquals('Minuman', $data[0]['category_name']);
    }

    public function test_search_items_returns_all_variant_items_for_variant_products(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kemeja Formal Pria',
            'code' => 'KFP-01',
            'product_type' => ProductTypeEnum::BASIC,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
        ]);

        $variantM = ProductItem::create([
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'uom_id' => $this->uom->id,
            'name' => 'Kemeja Formal Pria - Ukuran M',
            'sku' => 'KFP-M',
            'barcode' => '899000111',
            'item_type' => 'variant_sku',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $variantM->prices()->create([
            'product_id' => $product->id,
            'outlet_id' => null,
            'amount' => 150000,
        ]);

        $variantL = ProductItem::create([
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'uom_id' => $this->uom->id,
            'name' => 'Kemeja Formal Pria - Ukuran L',
            'sku' => 'KFP-L',
            'barcode' => '899000222',
            'item_type' => 'variant_sku',
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $variantL->prices()->create([
            'product_id' => $product->id,
            'outlet_id' => null,
            'amount' => 160000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items?search=Kemeja");

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data);

        $skus = collect($data)->pluck('sku')->toArray();
        $this->assertContains('KFP-M', $skus);
        $this->assertContains('KFP-L', $skus);

        $itemM = collect($data)->firstWhere('sku', 'KFP-M');
        $this->assertEquals($variantM->id, $itemM['id']);
        $this->assertEquals($variantM->id, $itemM['product_item_id']);
        $this->assertEquals($product->id, $itemM['product_id']);
        $this->assertEquals(150000, $itemM['price']);
    }

    public function test_search_items_returns_service_and_bundle_products(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Jasa Pemasangan & Setup',
            'code' => 'SRV-SETUP',
            'product_type' => ProductTypeEnum::SERVICE,
            'is_show' => true,
            'sellable' => true,
        ]);

        $service->prices()->create([
            'outlet_id' => null,
            'amount' => 75000,
        ]);

        $bundle = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Paket Hemat Kantor',
            'code' => 'BND-KTR',
            'product_type' => ProductTypeEnum::BUNDLE,
            'is_show' => true,
            'sellable' => true,
        ]);

        $bundle->prices()->create([
            'outlet_id' => null,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items");

        $response->assertOk();
        $data = $response->json();

        $srvItem = collect($data)->firstWhere('code', 'SRV-SETUP');
        $this->assertNotNull($srvItem);
        $this->assertEquals('service', $srvItem['product_type']);
        $this->assertEquals(75000, $srvItem['price']);

        $bndItem = collect($data)->firstWhere('code', 'BND-KTR');
        $this->assertNotNull($bndItem);
        $this->assertEquals('bundle', $bndItem['product_type']);
        $this->assertEquals(500000, $bndItem['price']);
    }

    public function test_search_items_respects_outlet_specific_price_and_availability(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Robusta 250g',
            'code' => 'ROB-250',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        // General price: 30000, Outlet specific price: 35000
        $product->prices()->createMany([
            ['outlet_id' => null, 'amount' => 30000],
            ['outlet_id' => $this->outlet->id, 'amount' => 35000],
        ]);

        // 1. Without outlet filter -> general price
        $resGeneral = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items?search=ROB-250");
        $resGeneral->assertOk();
        $this->assertEquals(30000, $resGeneral->json('0.price'));

        // 2. With outlet filter -> outlet specific price
        $resOutlet = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items?search=ROB-250&outlet_id={$this->outlet->id}");
        $resOutlet->assertOk();
        $this->assertEquals(35000, $resOutlet->json('0.price'));
    }

    public function test_search_items_zero_dependency_on_inventory_domain(): void
    {
        // Product created without any InventoryItem or InventoryBalance
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Barang Non Stok Murni',
            'code' => 'NON-STK',
            'product_type' => ProductTypeEnum::BASIC,
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
        ]);

        $product->prices()->create([
            'outlet_id' => null,
            'amount' => 20000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/products/items?search=NON-STK");

        $response->assertOk();
        $this->assertNotEmpty($response->json());
        $this->assertEquals('Barang Non Stok Murni', $response->json('0.name'));
        $this->assertEquals(20000, $response->json('0.price'));
    }
}
