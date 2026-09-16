<?php

namespace Tests\Unit\Models;

use App\Enums\FeatureEnum;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_cached_and_get_active_cached(): void
    {
        $feature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $activePlan = SubscriptionPlan::factory()->create([
            'code' => 'basic',
            'name' => 'Basic Plan',
            'price_per_outlet' => 50000,
            'max_outlet' => 5,
            'is_active' => true,
            'is_public' => true,
        ]);
        $activePlan->systemFeatures()->attach($feature->id);

        $inactivePlan = SubscriptionPlan::factory()->create([
            'code' => 'legacy',
            'name' => 'Legacy Plan',
            'price_per_outlet' => 25000,
            'max_outlet' => 1,
            'is_active' => false,
            'is_public' => false,
        ]);

        $allCached = SubscriptionPlan::getAllCached();
        $this->assertCount(2, $allCached);

        $activeCached = SubscriptionPlan::getActiveCached();
        $this->assertCount(1, $activeCached);
        $this->assertSame('basic', $activeCached->first()->code);
    }

    public function test_find_by_code_cached_and_find_cached(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'code' => 'pro',
            'name' => 'Pro Plan',
            'price_per_outlet' => 150000,
            'max_outlet' => 20,
            'is_active' => true,
            'is_public' => true,
        ]);

        $foundByCode = SubscriptionPlan::findByCodeCached('pro');
        $this->assertNotNull($foundByCode);
        $this->assertSame($plan->id, $foundByCode->id);

        $foundById = SubscriptionPlan::findCached($plan->id);
        $this->assertNotNull($foundById);
        $this->assertSame('pro', $foundById->code);

        $this->assertNull(SubscriptionPlan::findByCodeCached('non_existent'));
    }

    public function test_active_feature_codes_and_enums_cached(): void
    {
        $feature1 = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $feature2 = Feature::factory()->create([
            'code' => FeatureEnum::RECIPE_MANAGEMENT->value,
            'name' => 'Resep',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'code' => 'basic',
            'name' => 'Basic Plan',
            'price_per_outlet' => 50000,
            'is_active' => true,
            'is_public' => true,
        ]);

        $plan->systemFeatures()->attach([$feature1->id, $feature2->id]);
        $plan->clearFeatureCache();

        $codes = $plan->activeFeatureCodes();
        $this->assertContains(FeatureEnum::POS_CASHIER->value, $codes);
        $this->assertContains(FeatureEnum::RECIPE_MANAGEMENT->value, $codes);

        $enums = $plan->activeFeatureEnums();
        $this->assertContains(FeatureEnum::POS_CASHIER, $enums);
        $this->assertContains(FeatureEnum::RECIPE_MANAGEMENT, $enums);
    }

    public function test_cache_invalidation_on_save_and_delete(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'code' => 'micro',
            'name' => 'Micro Plan',
            'price_per_outlet' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        $cached = SubscriptionPlan::getAllCached();
        $this->assertCount(1, $cached);

        // Updating plan triggers invalidation
        $plan->update(['name' => 'Micro Plan Updated']);
        $freshCached = SubscriptionPlan::getAllCached();
        $this->assertSame('Micro Plan Updated', $freshCached->firstWhere('code', 'micro')->name);

        // Deleting plan triggers invalidation
        $plan->delete();
        $emptyCached = SubscriptionPlan::getAllCached();
        $this->assertCount(0, $emptyCached);
    }
}
