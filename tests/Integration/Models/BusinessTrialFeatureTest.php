<?php

namespace Tests\Integration\Models;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTrialFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_features_come_from_selected_trial_subscription_plan(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $microPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::MICRO->value,
            'name' => 'Micro Plan',
            'price_per_outlet' => 29000,
            'is_active' => true,
            'is_public' => true,
            'has_trial' => true,
            'trial_duration_days' => 14,
        ]);
        $microPlan->systemFeatures()->attach([$posFeature->id]);
        $microPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Trial Business Micro',
            'owner_name' => 'Owner Trial',
            'email' => 'trial_micro@example.com',
            'phone' => '08123456781',
            'business_type_id' => $businessType->id,
            'has_used_trial' => true,
        ]);

        // Create trial subscription
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $microPlan->id,
            'status' => SubscriptionStatus::Active->value,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => now(),
            'expired_at' => Carbon::now()->addDays(14),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertContains(FeatureEnum::POS_CASHIER, $availableFeatures);
        $this->assertTrue($business->hasFeature(FeatureEnum::POS_CASHIER));
    }

    public function test_expired_trial_subscription_locks_all_features(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $microPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::MICRO->value,
            'name' => 'Micro Plan',
            'price_per_outlet' => 29000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $microPlan->systemFeatures()->attach([$posFeature->id]);
        $microPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Expired Trial Business',
            'owner_name' => 'Owner Expired',
            'email' => 'trial_expired@example.com',
            'phone' => '08123456783',
            'business_type_id' => $businessType->id,
            'has_used_trial' => true,
        ]);

        // Trial expired yesterday
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $microPlan->id,
            'status' => SubscriptionStatus::Active->value,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => now()->subDays(15),
            'expired_at' => Carbon::now()->subDay(),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertEmpty($availableFeatures);
        $this->assertFalse($business->hasFeature(FeatureEnum::POS_CASHIER));
    }
}
