<?php

namespace Tests\Feature\Master;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\ProductCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductCategoryControllerTest extends TestCase
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
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::PRODUCT_CATEGORIES->value]]
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
                'active_features' => [FeatureEnum::PRODUCT_CATEGORIES->value],
            ],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Category Manager',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        // Grant category permissions
        foreach ([
            PermissionEnum::CATEGORY_VIEW->value,
            PermissionEnum::CATEGORY_CREATE->value,
            PermissionEnum::CATEGORY_UPDATE->value,
            PermissionEnum::CATEGORY_DELETE->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_guest_cannot_access_categories(): void
    {
        $response = $this->get("http://{$this->appDomain}/master/categories");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_categories(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/master/categories");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
    }

    public function test_user_without_permission_cannot_view_categories_json(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->getJson("http://{$this->appDomain}/master/categories");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_categories_page(): void
    {
        ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/categories");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Category/Index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Minuman')
        );
    }

    public function test_user_can_create_category(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/categories", [
                'name' => 'Makanan Berat',
                'sort_order' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('product_categories', [
            'business_id' => $this->business->id,
            'name' => 'Makanan Berat',
        ]);
    }

    public function test_user_can_update_category(): void
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/categories/{$category->id}", [
                'name' => 'Updated Name',
                'sort_order' => 2,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_delete_category(): void
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Category to Delete',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/master/categories/{$category->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertSoftDeleted('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_can_reorder_categories(): void
    {
        $cat1 = ProductCategory::create(['business_id' => $this->business->id, 'name' => 'Cat 1', 'sort_order' => 1]);
        $cat2 = ProductCategory::create(['business_id' => $this->business->id, 'name' => 'Cat 2', 'sort_order' => 2]);

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/master/categories/reorder", [
                'categories' => [
                    ['id' => $cat1->id, 'parent_id' => null, 'sort_order' => 2],
                    ['id' => $cat2->id, 'parent_id' => null, 'sort_order' => 1],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);

        $this->assertEquals(2, $cat1->fresh()->sort_order);
        $this->assertEquals(1, $cat2->fresh()->sort_order);
    }

    public function test_tenant_isolation_cannot_access_other_business_categories(): void
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

        $otherCategory = ProductCategory::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Secret Category',
            'sort_order' => 1,
        ]);

        // Current user should not see other business's category in tree
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/categories");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Category/Index')
            ->has('categories', 0)
        );
    }
}
