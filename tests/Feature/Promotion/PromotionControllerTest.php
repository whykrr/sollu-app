<?php

namespace Tests\Feature\Promotion;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Enums\PromoType;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\InventoryItem;
use App\Models\Outlet;
use App\Models\Promo;
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
        Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Merdeka',
            'promo_type' => PromoType::Percentage->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 17,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promos.data', 1)
            ->where('promos.data.0.name', 'Promo Merdeka')
        );
    }

    public function test_promotions_list_is_isolated_to_business(): void
    {
        Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Tenant A',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
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

        Promo::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Promo Tenant B',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 10000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promos.data', 1)
            ->where('promos.data.0.name', 'Promo Tenant A')
        );
    }

    public function test_user_can_filter_and_sort_promotions(): void
    {
        Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Diskon Awal Bulan',
            'promo_type' => PromoType::Percentage->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Diskon Akhir Pekan',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Product->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions?search=Awal&status=draft&promo_type=percentage&sort=name&direction=asc");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Promotion/Index')
            ->has('promos.data', 1)
            ->where('promos.data.0.name', 'Diskon Awal Bulan')
        );
    }

    public function test_authorized_user_can_create_promo(): void
    {
        $item = new InventoryItem([
            'business_id' => $this->business->id,
            'name' => 'Menu Spesial',
            'item_type' => 'raw_material',
        ]);
        $item->minimum_stock = 5;
        $item->save();

        $payload = [
            'name' => 'Promo Spesial Menu',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Product->value,
            'discount_value' => 7500,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'applies_to_all_outlets' => false,
            'outlet_ids' => [$this->outlet->id],
            'inventory_item_ids' => [$item->id],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('promos', [
            'business_id' => $this->business->id,
            'name' => 'Promo Spesial Menu',
        ]);
    }

    public function test_authorized_user_can_update_draft_promo(): void
    {
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Draft Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $payload = [
            'name' => 'Updated Draft Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 8000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/promotions/{$promo->id}", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('promos', [
            'id' => $promo->id,
            'name' => 'Updated Draft Promo',
        ]);
    }

    public function test_authorized_user_cannot_update_promo_from_another_business(): void
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

        $promo = Promo::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $payload = [
            'name' => 'Hacked Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 99999,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/promotions/{$promo->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_delete_draft_promo(): void
    {
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Draft To Delete',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertDatabaseMissing('promos', [
            'id' => $promo->id,
        ]);
    }

    public function test_authorized_user_cannot_delete_promo_from_another_business(): void
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

        $promo = Promo::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_publish_and_unpublish_promo(): void
    {
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Draft To Publish',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        // Publish
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/publish");

        $response->assertStatus(302);
        $promo->refresh();
        $this->assertEquals(PromoStatus::Active, $promo->status);

        // Unpublish
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/unpublish");

        $response->assertStatus(302);
        $promo->refresh();
        $this->assertEquals(PromoStatus::Inactive, $promo->status);
    }

    public function test_authorized_user_cannot_publish_promo_from_another_business(): void
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

        $promo = Promo::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/promotions/{$promo->id}/publish");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_promo_show(): void
    {
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Show Test',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'name', 'promo_type', 'target_type', 'outlets', 'inventory_items',
        ]);
    }

    public function test_authorized_user_cannot_view_promo_show_from_another_business(): void
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

        $promo = Promo::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Promo',
            'promo_type' => PromoType::Fixed->value,
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(403);
    }
}
