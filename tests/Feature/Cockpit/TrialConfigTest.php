<?php

namespace Tests\Feature\Cockpit;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\CockpitUser;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::clearTrialCache();
    }

    public function test_guest_cannot_access_trial_config(): void
    {
        $response = $this->getJson(route('cockpit.config.trial.show'));
        $response->assertUnauthorized();
    }

    public function test_cockpit_user_can_get_trial_config(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Admin Cockpit',
            'email' => 'admin@cockpit.sollu.test',
            'password' => 'password',
        ]);

        $feature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $basicPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::BASIC->value,
            'name' => 'Basic Plan',
            'price_per_outlet' => 50000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $basicPlan->systemFeatures()->attach([$feature->id]);
        $basicPlan->clearFeatureCache();

        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->getJson(route('cockpit.config.trial.show'));

        $response->assertOk();
        $response->assertJsonStructure([
            'duration_days',
            'features',
            'all_features',
        ]);
        $response->assertJson([
            'duration_days' => 14,
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);
    }

    public function test_cockpit_user_can_update_trial_config(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Admin Cockpit',
            'email' => 'admin2@cockpit.sollu.test',
            'password' => 'password',
        ]);

        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $promoFeature = Feature::factory()->create([
            'code' => FeatureEnum::PROMO_MANAGEMENT->value,
            'name' => 'Promo Management',
            'is_active' => true,
        ]);

        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->put(route('cockpit.config.trial.update'), [
                'duration_days' => 30,
                'features' => [
                    FeatureEnum::POS_CASHIER->value,
                    FeatureEnum::PROMO_MANAGEMENT->value,
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(30, SystemSetting::getTrialDurationDays());

        $trialEnums = SystemSetting::getTrialFeatureEnumsCached();
        $this->assertCount(2, $trialEnums);
        $this->assertContains(FeatureEnum::POS_CASHIER, $trialEnums);
        $this->assertContains(FeatureEnum::PROMO_MANAGEMENT, $trialEnums);
    }

    public function test_trial_config_validation_rejects_invalid_data(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Admin Cockpit',
            'email' => 'admin3@cockpit.sollu.test',
            'password' => 'password',
        ]);

        // Invalid duration days (0) and invalid feature code
        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->put(route('cockpit.config.trial.update'), [
                'duration_days' => 0,
                'features' => ['non_existent_feature_code'],
            ]);

        $response->assertSessionHasErrors(['duration_days', 'features.0']);
    }
}
