<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeatureSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        return $user;
    }

    protected function subscribeBusinessToPlan(User $user, PlanEnum $planEnum = PlanEnum::BASIC): void
    {
        setPermissionsTeamId($user->business_id);
        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(1),
            'expired_at' => Carbon::now()->addDays(29),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_features_page(): void
    {
        $response = $this->get("http://{$this->appDomain}/settings/business/features");

        $response->assertStatus(302);
    }

    public function test_user_without_permission_cannot_access_features_page(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        $user->syncPermissions([]);
        $user->syncRoles([]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/business/features");

        $response->assertRedirect();
        $response->assertSessionHas('failed');
    }

    public function test_authorized_user_can_view_features_page_with_expected_props(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/business/features");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Business/Features')
            ->has('availableFeatures')
            ->has('activeFeatures')
            ->has('featureGroups')
        );
    }

    public function test_authorized_user_can_save_valid_active_features(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $business = $user->business;
        $availableFeatures = array_map(fn ($f) => $f->value, $business->getAvailablePlanFeatures());
        $featuresToEnable = array_slice($availableFeatures, 0, 3);

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/business/features", [
            'features' => $featuresToEnable,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $business->refresh();
        $this->assertEquals($featuresToEnable, $business->settings['active_features']);
    }

    public function test_saving_features_filters_out_features_not_in_plan(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::MICRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $business = $user->business;
        $availableFeatures = array_map(fn ($f) => $f->value, $business->getAvailablePlanFeatures());

        // Try to save a valid feature and an invalid feature (not in Micro plan)
        $validFeature = $availableFeatures[0] ?? FeatureEnum::POS_CASHIER->value;
        $invalidFeature = 'custom_role'; // Typically pro feature not in Micro

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/business/features", [
            'features' => [$validFeature, $invalidFeature],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $business->refresh();
        $savedFeatures = $business->settings['active_features'];
        $this->assertContains($validFeature, $savedFeatures);
        $this->assertNotContains($invalidFeature, $savedFeatures);
    }
}
