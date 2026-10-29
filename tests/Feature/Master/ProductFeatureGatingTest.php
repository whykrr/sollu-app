<?php

namespace Tests\Feature\Master;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductFeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected BusinessType $businessType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $this->businessType = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );
    }

    protected function createMerchantWithPlan(PlanEnum $planEnum): array
    {
        $business = Business::create([
            'name' => 'Gating Merchant '.$planEnum->value,
            'owner_name' => 'Owner',
            'email' => 'gating_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->businessType->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        if ($plan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $business->clearMemoizedFeatures();

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet '.$planEnum->value,
            'is_active' => true,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'User '.$planEnum->value,
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        foreach (PermissionEnum::cases() as $perm) {
            Permission::firstOrCreate(['name' => $perm->value, 'guard_name' => 'business']);
            $user->givePermissionTo($perm->value);
        }

        return [$business, $user, $outlet];
    }

    public function test_micro_plan_user_blocked_from_raw_materials_endpoints(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::MICRO);

        // JSON API request expects HTTP 403 with is_feature_locked
        $response = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/raw-materials");

        $response->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);
    }

    public function test_micro_plan_user_blocked_from_recipe_templates_endpoints(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::MICRO);

        $response = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/recipes");

        $response->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);
    }

    public function test_micro_plan_user_blocked_from_modifiers_endpoints(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::MICRO);

        $response = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/modifiers");

        $response->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);
    }

    public function test_basic_plan_user_blocked_from_recipes_and_modifiers(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::BASIC);

        // Blocked from recipes
        $recipeResp = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/recipes");

        $recipeResp->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_BOM->value,
            ]);

        // Blocked from modifiers
        $modifierResp = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/modifiers");

        $modifierResp->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::PRODUCT_MODIFIER->value,
            ]);
    }

    public function test_pro_plan_user_allowed_to_access_all_product_endpoints(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::PRO);

        $rawMaterialResp = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/raw-materials");
        $this->assertNotEquals(403, $rawMaterialResp->status());

        $recipeResp = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/recipes");
        $this->assertNotEquals(403, $recipeResp->status());

        $modifierResp = $this->actingAs($user, 'business')
            ->getJson("http://{$this->appDomain}/master/modifiers");
        $this->assertNotEquals(403, $modifierResp->status());
    }

    public function test_web_request_feature_lock_redirects_back_with_session_feature_locked(): void
    {
        [$business, $user] = $this->createMerchantWithPlan(PlanEnum::MICRO);

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/master/products")
            ->get("http://{$this->appDomain}/master/recipes");

        $response->assertStatus(302);
        $response->assertRedirect("http://{$this->appDomain}/master/products");
        $response->assertSessionHas('feature_locked');
        $sessionData = session('feature_locked');
        $this->assertEquals(FeatureEnum::PRODUCT_BOM->value, $sessionData['feature']);
    }
}
