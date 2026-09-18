<?php

namespace Tests\Feature\Master;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
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

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
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
        $outletA = \App\Models\Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet A',
        ]);

        $outletB = \App\Models\Outlet::create([
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
}
