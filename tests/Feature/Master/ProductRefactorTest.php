<?php

namespace Tests\Feature\Master;

use App\Constants\FlashDataVariable;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Jobs\Master\ImportProductJob;
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
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected ProductCategory $category;

    protected Uom $uom;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Refactor Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'refactor_'.uniqid().'@test.test',
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
            'name' => 'Central Outlet',
            'code' => 'OUT-01',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman',
        ]);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'Pcs'],
            ['name' => 'Pcs', 'category' => 'count']
        );

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Product Master Admin',
            'email' => 'admin_'.uniqid().'@test.test',
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

    public function test_rejects_duplicate_sku_in_same_business(): void
    {
        // Create an existing product item with SKU
        ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Susu Original',
            'sku' => 'SKU-KOPI-001',
            'barcode' => '899123456001',
            'item_type' => 'variant_sku',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Kopi Susu Gula Aren',
                'code' => 'SKU-KOPI-001', // duplicate SKU
                'barcode' => '899123456002',
                'product_type' => 'basic',
                'base_price' => 20000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_rejects_duplicate_barcode_in_same_business(): void
    {
        ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Susu Original',
            'sku' => 'SKU-KOPI-001',
            'barcode' => '899123456001',
            'item_type' => 'variant_sku',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Kopi Susu Pandan',
                'code' => 'SKU-KOPI-002',
                'barcode' => '899123456001', // duplicate Barcode
                'product_type' => 'basic',
                'base_price' => 20000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertSessionHasErrors(['barcode']);
    }

    public function test_rejects_cross_field_collision_where_barcode_equals_existing_sku(): void
    {
        ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Teh Tarik',
            'sku' => 'CODE-TEH-123',
            'barcode' => '899999999123',
            'item_type' => 'variant_sku',
            'is_active' => true,
        ]);

        // Attempt to create a product where barcode equals the existing SKU 'CODE-TEH-123'
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Teh Hijau',
                'code' => 'CODE-TEH-456',
                'barcode' => 'CODE-TEH-123', // Collides with existing SKU
                'product_type' => 'basic',
                'base_price' => 15000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertSessionHasErrors(['barcode']);
    }

    public function test_rejects_cross_field_collision_where_sku_equals_existing_barcode(): void
    {
        ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Teh Tarik',
            'sku' => 'CODE-TEH-123',
            'barcode' => '899999999123',
            'item_type' => 'variant_sku',
            'is_active' => true,
        ]);

        // Attempt to create a product where code (SKU) equals the existing barcode '899999999123'
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Teh Lemon',
                'code' => '899999999123', // Collides with existing Barcode
                'barcode' => '899999999999',
                'product_type' => 'basic',
                'base_price' => 15000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_allows_same_sku_and_barcode_in_different_business(): void
    {
        // Another business
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567899',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        ProductItem::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Produk Toko Lain',
            'sku' => 'SHARED-SKU-999',
            'barcode' => 'SHARED-BARCODE-999',
            'item_type' => 'variant_sku',
            'is_active' => true,
        ]);

        // Now creating product in our business with same SKU & Barcode should SUCCEED
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Produk Toko Saya',
                'code' => 'SHARED-SKU-999',
                'barcode' => 'SHARED-BARCODE-999',
                'product_type' => 'basic',
                'base_price' => 25000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_items', [
            'business_id' => $this->business->id,
            'sku' => 'SHARED-SKU-999',
            'barcode' => 'SHARED-BARCODE-999',
        ]);
    }

    public function test_update_product_allows_preserving_its_own_sku_and_barcode(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Roti Bakar Cokelat',
                'code' => 'RBC-001',
                'barcode' => '899777001',
                'product_type' => 'basic',
                'base_price' => 12000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);
        $response->assertSessionHasNoErrors();

        $product = Product::where('code', 'RBC-001')->firstOrFail();

        // Update name and price, but keep same code & barcode
        $updateResponse = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", [
                'name' => 'Roti Bakar Cokelat Keju',
                'code' => 'RBC-001',
                'barcode' => '899777001',
                'product_type' => 'basic',
                'base_price' => 15000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertEquals('Roti Bakar Cokelat Keju', $product->fresh()->name);
    }

    public function test_rejects_in_payload_duplicate_variant_skus(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Baju Polos',
                'code' => 'BAJU-001',
                'product_type' => 'basic',
                'has_variant' => true,
                'base_price' => 50000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
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
                        'name' => 'Baju Polos - S',
                        'sku' => 'BAJU-VAR-SAME',
                        'barcode' => '11111111',
                        'price' => 50000,
                        'options' => ['Ukuran' => 'S'],
                    ],
                    [
                        'name' => 'Baju Polos - M',
                        'sku' => 'BAJU-VAR-SAME', // In-payload duplicate SKU!
                        'barcode' => '22222222',
                        'price' => 50000,
                        'options' => ['Ukuran' => 'M'],
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['variant_combinations.1.sku']);
    }

    public function test_redirect_preserves_query_parameters_via_redirect_back(): void
    {
        $filterUrl = "http://{$this->appDomain}/master/products?category_id={$this->category->id}&search=kopi&page=2";

        $response = $this->actingAs($this->user, 'business')
            ->from($filterUrl)
            ->post("http://{$this->appDomain}/master/products", [
                'name' => 'Kopi Luwak Asli',
                'code' => 'KPL-001',
                'barcode' => '899333444111',
                'product_type' => 'basic',
                'base_price' => 35000,
                'track_inventory' => false,
                'is_show' => true,
                'sellable' => true,
            ]);

        // Verifying it redirects back to the table with original filter query string preserved
        $response->assertRedirect($filterUrl);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);
    }

    public function test_import_template_endpoint_returns_binary_excel_download(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/products/import-template");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_job_rejects_variant_rows_when_tenant_lacks_product_variant_feature(): void
    {
        // Create business without PRODUCT_VARIANT
        $limitedBusiness = Business::create([
            'name' => 'Basic Merchant Without Variants',
            'owner_name' => 'Basic Owner',
            'email' => 'basic_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
            'settings' => [
                // Only basic features, explicitly omit PRODUCT_VARIANT
                'active_features' => [
                    FeatureEnum::PRODUCT_CATALOG->value,
                    FeatureEnum::POS->value,
                ],
            ],
        ]);

        $user = User::create([
            'business_id' => $limitedBusiness->id,
            'name' => 'Limited User',
            'email' => 'limited_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        Storage::fake('local');
        $csvContent = implode("\n", [
            'Nama Produk*,Kategori,Tipe Produk*,Satuan,Harga Jual,Dapat Dijual,Kelola Stok,SKU/Kode,Barcode,Nama Varian,Opsi Varian,Harga Varian,SKU Varian,Barcode Varian',
            'Kaos Distro,Pakaian,Varian,Pcs,100000,Ya,Tidak,,,Ukuran,XL,100000,KS-XL,99887766',
        ]);
        $filePath = 'imports/test_variant_import.csv';
        Storage::disk('local')->put($filePath, $csvContent);

        $job = new ImportProductJob(
            user: $user,
            filePath: $filePath,
            businessId: $limitedBusiness->id
        );

        $job->handle();

        // The product should NOT be created because variant is rejected
        $this->assertDatabaseMissing('products', [
            'business_id' => $limitedBusiness->id,
            'name' => 'Kaos Distro',
        ]);
    }
}
