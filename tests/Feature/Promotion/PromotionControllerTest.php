<?php

namespace Tests\Feature\Promotion;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

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
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::PROMO_MANAGEMENT->value]]
        );

        /** @var Business $business */
        $business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::PROMO_MANAGEMENT->value],
            ],
        ]);
        $this->business = $business;

        $basicPlan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        /** @var User $user */
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Manager',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);
        $this->user = $user;

        /** @var Outlet $outlet */
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
        ]);
        $this->outlet = $outlet;

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::PROMO_VIEW->value,
            PermissionEnum::PROMO_CREATE->value,
            PermissionEnum::PROMO_UPDATE->value,
            PermissionEnum::PROMO_DELETE->value,
            PermissionEnum::PROMO_PUBLISH->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_guest_cannot_access_promotions(): void
    {
        $response = $this->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_promotions(): void
    {
        /** @var User $unauthorizedUser */
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
    }

    public function test_authorized_user_can_view_promotions_page(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Merdeka',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 17,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promotions.data', 1)
            ->where('promotions.data.0.name', 'Promo Merdeka')
        );
    }

    public function test_promotions_list_is_isolated_to_business(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Tenant A',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Promo Tenant B',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 10000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promotions.data', 1)
            ->where('promotions.data.0.name', 'Promo Tenant A')
        );
    }

    public function test_user_can_filter_and_sort_promotions(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Diskon Awal Bulan',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Diskon Akhir Pekan',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Product->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions?search=Awal&status=draft&discount_type=percentage&sort=name&direction=asc");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promotions.data', 1)
            ->where('promotions.data.0.name', 'Diskon Awal Bulan')
        );
    }

    public function test_authorized_user_can_create_draft_promotion(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Master Product',
            'product_type' => 'basic',
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Menu Spesial Varian',
            'item_type' => 'variant_sku',
        ]);

        $payload = [
            'name' => 'Promo Spesial Menu',
            'application_mode' => PromotionApplicationMode::Manual->value,
            'promo_code' => 'SPESIAL50',
            'target_scope' => PromotionTargetScope::Variant->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 7500,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'applies_to_all_outlets' => false,
            'outlet_ids' => [$this->outlet->id],
            'product_item_ids' => [$item->id],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('promotions', [
            'business_id' => $this->business->id,
            'name' => 'Promo Spesial Menu',
            'promo_code' => 'SPESIAL50',
            'status' => PromotionStatus::Draft->value,
        ]);

        $promo = Promotion::where('name', 'Promo Spesial Menu')->first();
        $this->assertCount(1, $promo->outlets);
        $this->assertCount(1, $promo->productItems);
    }

    public function test_create_promotion_validation_fails_with_indonesian_messages(): void
    {
        // 1. Empty name
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", []);

        $response->assertSessionHasErrors([
            'name' => 'Nama promo wajib diisi.',
            'application_mode' => 'Mode aplikasi promo tidak valid.',
            'target_scope' => 'Cakupan target promo tidak valid.',
            'discount_type' => 'Tipe diskon tidak valid.',
            'discount_value' => 'Nilai diskon wajib diisi.',
            'applies_to_all_outlets' => 'Cakupan outlet wajib ditentukan.',
            'start_date' => 'Tanggal mulai wajib diisi.',
            'end_date' => 'Tanggal berakhir wajib diisi.',
        ]);

        // 2. Manual mode without promo code
        $responseManual = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Test Promo',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 150, // Invalid percentage
                'start_date' => Carbon::now()->addDays(5)->toDateString(),
                'end_date' => Carbon::now()->toDateString(), // before start_date
                'applies_to_all_outlets' => true,
            ]);

        $responseManual->assertSessionHasErrors([
            'promo_code' => 'Kode promo wajib diisi jika mode manual dan hanya boleh huruf, angka, strip, dan underscore.',
            'discount_value' => 'Nilai diskon persentase harus antara 0.01% hingga 100%.',
            'end_date' => 'Tanggal berakhir tidak boleh mendahului tanggal mulai.',
        ]);
    }

    public function test_authorized_user_can_update_draft_promotion(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Old Draft Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $payload = [
            'name' => 'Updated Draft Promo',
            'application_mode' => PromotionApplicationMode::Manual->value,
            'promo_code' => 'UPDATED8K',
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 8000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/promotions/{$promo->id}", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('promotions', [
            'id' => $promo->id,
            'name' => 'Updated Draft Promo',
            'promo_code' => 'UPDATED8K',
        ]);
    }

    public function test_cannot_update_active_promotion_directly(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Active Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $payload = [
            'name' => 'Attempt Edit Active',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 10000,
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/promotions/{$promo->id}", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Promo yang sedang aktif tidak dapat diubah langsung. Nonaktifkan promo terlebih dahulu.');
    }

    public function test_authorized_user_cannot_update_promotion_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $promo = Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $payload = [
            'name' => 'Hacked Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 99999,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/promotions/{$promo->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_delete_draft_promotion(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Draft To Delete',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertDatabaseMissing('promotions', [
            'id' => $promo->id,
        ]);
    }

    public function test_cannot_delete_active_promotion(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Active To Delete',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Hanya promo berstatus Draf yang dapat dihapus.');

        $this->assertDatabaseHas('promotions', [
            'id' => $promo->id,
        ]);
    }

    public function test_authorized_user_cannot_delete_promotion_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $promo = Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_publish_and_unpublish_promotion(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Draft To Publish',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        // Publish
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/publish");

        $response->assertStatus(302);
        $promo->refresh();
        $this->assertEquals(PromotionStatus::Active, $promo->status);
        $this->assertNotNull($promo->published_at);
        $this->assertEquals($this->user->id, $promo->published_by);

        // Unpublish
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/unpublish");

        $response->assertStatus(302);
        $promo->refresh();
        $this->assertEquals(PromotionStatus::Inactive, $promo->status);
    }

    public function test_user_cannot_publish_promotion_with_past_end_date(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Past Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDay()->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/publish");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Tanggal berakhir promo sudah terlewat.');

        $promo->refresh();
        $this->assertEquals(PromotionStatus::Draft, $promo->status);
    }

    public function test_authorized_user_cannot_publish_promotion_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $promo = Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/publish");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_promotion_show(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Show Test',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'name', 'discount_type', 'target_scope', 'outlets', 'categories', 'products', 'product_items',
        ]);
    }

    public function test_authorized_user_cannot_view_promotion_show_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $promo = Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(403);
    }

    public function test_create_promotion_with_transaction_scope_and_empty_relations_passes(): void
    {
        $payload = [
            'name' => 'Promo Transaksi Bersih',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 15,
            'min_subtotal' => null,
            'min_quantity' => null,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
            'category_ids' => [],
            'product_ids' => [],
            'product_item_ids' => [],
            'outlet_ids' => [],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('promotions', [
            'business_id' => $this->business->id,
            'name' => 'Promo Transaksi Bersih',
            'target_scope' => PromotionTargetScope::Transaction->value,
            'promo_code' => null,
        ]);

        $promo = Promotion::where('name', 'Promo Transaksi Bersih')->first();
        $this->assertCount(0, $promo->categories);
        $this->assertCount(0, $promo->products);
        $this->assertCount(0, $promo->productItems);
        $this->assertCount(0, $promo->outlets);
    }

    public function test_create_promotion_with_category_scope_validates_required_categories(): void
    {
        // 1. Without category_ids -> fails
        $responseFail = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Kategori Kosong',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Category->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'category_ids' => [],
            ]);

        $responseFail->assertSessionHasErrors(['category_ids']);

        // 2. With valid category -> passes & synced
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman',
        ]);

        $responseSuccess = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Kategori Minuman',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Category->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'category_ids' => [$category->id],
            ]);

        $responseSuccess->assertStatus(302);
        $responseSuccess->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $promo = Promotion::where('name', 'Promo Kategori Minuman')->first();
        $this->assertCount(1, $promo->categories);
        $this->assertEquals($category->id, $promo->categories->first()->id);
    }

    public function test_create_promotion_with_product_scope_validates_required_products(): void
    {
        // 1. Without product_ids -> fails
        $responseFail = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Produk Kosong',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Product->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'product_ids' => [],
            ]);

        $responseFail->assertSessionHasErrors(['product_ids']);

        // 2. With valid product -> passes & synced
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Susu Gula Aren',
            'product_type' => 'basic',
        ]);

        $responseSuccess = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Kopi Susu',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Product->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'product_ids' => [$product->id],
            ]);

        $responseSuccess->assertStatus(302);
        $responseSuccess->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $promo = Promotion::where('name', 'Promo Kopi Susu')->first();
        $this->assertCount(1, $promo->products);
        $this->assertEquals($product->id, $promo->products->first()->id);
    }

    public function test_create_promotion_with_variant_scope_validates_required_variants(): void
    {
        // 1. Without product_item_ids -> fails
        $responseFail = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Varian Kosong',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Variant->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'product_item_ids' => [],
            ]);

        $responseFail->assertSessionHasErrors(['product_item_ids']);

        // 2. With valid product item -> passes & synced
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kemeja Pria',
            'product_type' => 'basic',
        ]);
        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Kemeja Pria Ukuran L',
            'item_type' => 'variant_sku',
        ]);

        $responseSuccess = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Kemeja L',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Variant->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 10000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'product_item_ids' => [$item->id],
            ]);

        $responseSuccess->assertStatus(302);
        $responseSuccess->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $promo = Promotion::where('name', 'Promo Kemeja L')->first();
        $this->assertCount(1, $promo->productItems);
        $this->assertEquals($item->id, $promo->productItems->first()->id);
    }

    public function test_create_promotion_cross_tenant_relations_are_rejected(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherCat = ProductCategory::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Cat',
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Injeksi Tenant',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Category->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'category_ids' => [$otherCat->id],
            ]);

        $response->assertSessionHasErrors(['category_ids.0']);
    }

    public function test_create_promotion_automatic_mode_ignores_and_nulls_promo_code(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Otomatis Bersih',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'promo_code' => 'ACCIDENTAL_CODE',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);

        $response->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Otomatis Bersih')->first();
        $this->assertNotNull($promo);
        $this->assertNull($promo->promo_code);
    }

    public function test_create_promotion_manual_mode_requires_valid_promo_code(): void
    {
        // 1. Missing promo code -> fails
        $response1 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Manual Tanpa Kode',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'promo_code' => '',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);
        $response1->assertSessionHasErrors(['promo_code']);

        // 2. Invalid characters in promo code -> fails
        $response2 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Manual Kode Simbol',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'promo_code' => 'KODE DISKON!',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);
        $response2->assertSessionHasErrors(['promo_code']);

        // 3. Valid lowercase code is converted to uppercase & trimmed
        $response3 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Manual Huruf Kecil',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'promo_code' => '  hemat-10_ok  ',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);
        $response3->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Manual Huruf Kecil')->first();
        $this->assertEquals('HEMAT-10_OK', $promo->promo_code);
    }

    public function test_create_promotion_manual_mode_duplicate_code_in_same_business_fails(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Existing',
            'application_mode' => PromotionApplicationMode::Manual->value,
            'promo_code' => 'HEMAT10',
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Duplicate Code',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'promo_code' => 'hemat10',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);

        $response->assertSessionHasErrors(['promo_code']);
    }

    public function test_create_promotion_manual_mode_same_code_in_different_business_passes(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo Code',
            'application_mode' => PromotionApplicationMode::Manual->value,
            'promo_code' => 'SHAREDCODE',
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Our Promo Shared Code',
                'application_mode' => PromotionApplicationMode::Manual->value,
                'promo_code' => 'SHAREDCODE',
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 10,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('promotions', [
            'business_id' => $this->business->id,
            'name' => 'Our Promo Shared Code',
            'promo_code' => 'SHAREDCODE',
        ]);
    }

    public function test_create_promotion_percentage_discount_boundary_rules(): void
    {
        // 1. > 100% -> fails
        $responseOver = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo 105 Percent',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 100.01,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);
        $responseOver->assertSessionHasErrors(['discount_value']);

        // 2. Exactly 100% with max_discount_amount -> passes
        $response100 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo 100 Percent Cap 25k',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Percentage->value,
                'discount_value' => 100,
                'max_discount_amount' => 25000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);
        $response100->assertStatus(302);

        $promo = Promotion::where('name', 'Promo 100 Percent Cap 25k')->first();
        $this->assertEquals(100, (float) $promo->discount_value);
        $this->assertEquals(25000, (float) $promo->max_discount_amount);
    }

    public function test_create_promotion_fixed_discount_nulls_max_discount_amount(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Fixed With Redundant Cap',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 15000,
                'max_discount_amount' => 10000, // Should be nulled
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
            ]);

        $response->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Fixed With Redundant Cap')->first();
        $this->assertEquals(15000, (float) $promo->discount_value);
        $this->assertNull($promo->max_discount_amount);
    }

    public function test_create_promotion_applies_to_all_outlets_true_clears_outlet_pivot(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Semua Outlet Clean',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'outlet_ids' => [$this->outlet->id],
            ]);

        $response->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Semua Outlet Clean')->first();
        $this->assertTrue($promo->applies_to_all_outlets);
        $this->assertCount(0, $promo->outlets);
    }

    public function test_create_promotion_specific_outlets_validates_required_and_tenant_ownership(): void
    {
        // 1. Without outlet_ids -> fails
        $responseFail = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Outlet Spesifik Kosong',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => false,
                'outlet_ids' => [],
            ]);
        $responseFail->assertSessionHasErrors(['outlet_ids']);

        // 2. With other tenant outlet -> fails
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);
        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Cabang Lain',
        ]);

        $responseTenant = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Outlet Tenant Lain',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => false,
                'outlet_ids' => [$otherOutlet->id],
            ]);
        $responseTenant->assertSessionHasErrors(['outlet_ids.0']);

        // 3. With own outlet -> passes and syncs
        $responseSuccess = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Outlet Cabang Utama',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => false,
                'outlet_ids' => [$this->outlet->id],
            ]);
        $responseSuccess->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Outlet Cabang Utama')->first();
        $this->assertFalse($promo->applies_to_all_outlets);
        $this->assertCount(1, $promo->outlets);
        $this->assertEquals($this->outlet->id, $promo->outlets->first()->id);
    }

    public function test_create_promotion_happy_hour_time_validation_rules(): void
    {
        // 1. start_time filled but end_time empty -> fails
        $response1 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Jam Ganjil 1',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'start_time' => '14:00',
                'end_time' => '',
            ]);
        $response1->assertSessionHasErrors(['end_time']);

        // 2. end_time before or equal to start_time -> fails
        $response2 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Jam Ganjil 2',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'start_time' => '17:00',
                'end_time' => '14:00',
            ]);
        $response2->assertSessionHasErrors(['end_time']);

        // 3. Valid happy hour -> passes
        $response3 = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Sore Happy Hour',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'start_time' => '14:00',
                'end_time' => '17:00',
                'days_of_week' => [1, 2, 3, 4, 5],
            ]);
        $response3->assertStatus(302);

        $promo = Promotion::where('name', 'Promo Sore Happy Hour')->first();
        $this->assertEquals('14:00', $promo->start_time->format('H:i'));
        $this->assertEquals('17:00', $promo->end_time->format('H:i'));
        $this->assertEquals([1, 2, 3, 4, 5], $promo->days_of_week);
    }

    public function test_create_promotion_days_of_week_invalid_fails(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", [
                'name' => 'Promo Hari Invalid',
                'application_mode' => PromotionApplicationMode::Automatic->value,
                'target_scope' => PromotionTargetScope::Transaction->value,
                'discount_type' => PromotionDiscountType::Fixed->value,
                'discount_value' => 5000,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'applies_to_all_outlets' => true,
                'days_of_week' => [0, 8],
            ]);

        $response->assertSessionHasErrors(['days_of_week.0', 'days_of_week.1']);
    }
}
