<?php

namespace Tests\Unit\Models;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTrialFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::clearTrialCache();
    }

    public function test_trial_features_fallback_to_basic_plan_when_not_configured(): void
    {
        $posFeature = Feature::factory()->create([
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
        $basicPlan->systemFeatures()->attach([$posFeature->id]);
        $basicPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Trial Business Default',
            'owner_name' => 'Owner Trial',
            'email' => 'trial_default@example.com',
            'phone' => '08123456781',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertContains(FeatureEnum::POS_CASHIER, $availableFeatures);
        $this->assertTrue($business->hasFeature(FeatureEnum::POS_CASHIER));
    }

    public function test_trial_features_use_dynamic_system_setting_configuration(): void
    {
        $customerFeature = Feature::factory()->create([
            'code' => FeatureEnum::CUSTOMER_MANAGEMENT->value,
            'name' => 'Manajemen Pelanggan',
            'is_active' => true,
        ]);

        $promoFeature = Feature::factory()->create([
            'code' => FeatureEnum::PROMO_MANAGEMENT->value,
            'name' => 'Manajemen Promo',
            'is_active' => true,
        ]);

        // Explicitly set trial features in SystemSetting
        SystemSetting::set('trial_features', [
            FeatureEnum::CUSTOMER_MANAGEMENT->value,
            FeatureEnum::PROMO_MANAGEMENT->value,
        ], 'subscription');

        $businessType = BusinessType::factory()->create([
            'code' => 'fnb',
            'features' => [
                FeatureEnum::CUSTOMER_MANAGEMENT->value,
                FeatureEnum::PROMO_MANAGEMENT->value,
                FeatureEnum::POS_CASHIER->value,
            ],
        ]);

        $business = Business::create([
            'name' => 'Custom Trial Business',
            'owner_name' => 'Owner Custom',
            'email' => 'trial_custom@example.com',
            'phone' => '08123456782',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertContains(FeatureEnum::CUSTOMER_MANAGEMENT, $availableFeatures);
        $this->assertContains(FeatureEnum::PROMO_MANAGEMENT, $availableFeatures);
        $this->assertNotContains(FeatureEnum::POS_CASHIER, $availableFeatures);

        $this->assertTrue($business->hasFeature(FeatureEnum::CUSTOMER_MANAGEMENT));
        $this->assertTrue($business->hasFeature(FeatureEnum::PROMO_MANAGEMENT));
        $this->assertFalse($business->hasFeature(FeatureEnum::POS_CASHIER));
    }

    public function test_expired_trial_without_active_subscription_locks_all_features(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        SystemSetting::set('trial_features', [FeatureEnum::POS_CASHIER->value], 'subscription');

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        // Trial expired yesterday
        $business = Business::create([
            'name' => 'Expired Trial Business',
            'owner_name' => 'Owner Expired',
            'email' => 'trial_expired@example.com',
            'phone' => '08123456783',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDay(),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertEmpty($availableFeatures);
        $this->assertFalse($business->hasFeature(FeatureEnum::POS_CASHIER));
    }

    public function test_system_setting_trial_duration_days_default_and_custom(): void
    {
        // Default when not set
        $this->assertSame(14, SystemSetting::getTrialDurationDays());

        // Custom duration
        SystemSetting::set('trial_default_duration_days', 30, 'subscription');
        $this->assertSame(30, SystemSetting::getTrialDurationDays());
    }
}
