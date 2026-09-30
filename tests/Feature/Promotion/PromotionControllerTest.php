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
}
