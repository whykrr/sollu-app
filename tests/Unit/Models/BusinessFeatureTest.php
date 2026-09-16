<?php

namespace Tests\Unit\Models;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_on_trial_uses_cached_micro_plan_features(): void
    {
        $feature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $microPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::MICRO->value,
            'name' => 'Micro Plan',
            'price_per_outlet' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);
        $microPlan->systemFeatures()->attach($feature->id);
        $microPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Trial Business',
            'owner_name' => 'Owner Trial',
            'email' => 'trial@example.com',
            'phone' => '08123456789',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertContains(FeatureEnum::POS_CASHIER, $availableFeatures);
        $this->assertTrue($business->hasFeature(FeatureEnum::POS_CASHIER));
        $this->assertFalse($business->hasFeature(FeatureEnum::RECIPE_MANAGEMENT));
    }

    public function test_business_with_active_subscription_resolves_cached_plan(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $recipeFeature = Feature::factory()->create([
            'code' => FeatureEnum::RECIPE_MANAGEMENT->value,
            'name' => 'Resep',
            'is_active' => true,
        ]);

        $proPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::PRO->value,
            'name' => 'Pro Plan',
            'price_per_outlet' => 100000,
            'max_outlet' => 10,
            'is_active' => true,
            'is_public' => true,
        ]);
        $proPlan->systemFeatures()->attach([$posFeature->id, $recipeFeature->id]);
        $proPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value, FeatureEnum::RECIPE_MANAGEMENT->value],
        ]);

        $business = Business::create([
            'name' => 'Pro Business',
            'owner_name' => 'Owner Pro',
            'email' => 'pro@example.com',
            'phone' => '08123456780',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDays(1),
        ]);

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addMonth(),
        ]);

        $availableFeatures = $business->getAvailablePlanFeatures();
        $this->assertContains(FeatureEnum::POS_CASHIER, $availableFeatures);
        $this->assertContains(FeatureEnum::RECIPE_MANAGEMENT, $availableFeatures);
        $this->assertTrue($business->hasFeature(FeatureEnum::POS_CASHIER));
        $this->assertTrue($business->hasFeature(FeatureEnum::RECIPE_MANAGEMENT));
        $this->assertSame(10, $business->maxOutletsAllowed());
    }
}
