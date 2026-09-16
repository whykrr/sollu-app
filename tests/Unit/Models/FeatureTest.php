<?php

namespace Tests\Unit\Models;

use App\Enums\FeatureEnum;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_caches_all_features_and_clears_on_change(): void
    {
        Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'group' => 'Penjualan',
            'group_label' => 'Penjualan & Kasir',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $cached = Feature::getAllCached();
        $this->assertCount(1, $cached);

        $options = Feature::options();
        $this->assertArrayHasKey(FeatureEnum::POS_CASHIER->value, $options);

        $grouped = Feature::grouped();
        $this->assertArrayHasKey('Penjualan & Kasir', $grouped);

        // Feature change invalidates cache
        $feature2 = Feature::factory()->create([
            'code' => FeatureEnum::RECIPE_MANAGEMENT->value,
            'name' => 'Manajemen Resep',
            'group' => 'Inventory',
            'group_label' => 'Inventaris & Bahan',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $freshCached = Feature::getAllCached();
        $this->assertCount(2, $freshCached);

        // Plan caches are also invalidated
        $plan = SubscriptionPlan::factory()->create([
            'code' => 'basic',
            'name' => 'Basic',
            'price_per_outlet' => 50000,
            'is_active' => true,
        ]);
        $plan->systemFeatures()->attach([$feature2->id]);
        $this->assertCount(1, SubscriptionPlan::getAllCached());

        $feature2->update(['name' => 'Resep Updated']);
        // Plan cache cleared
        $planFresh = SubscriptionPlan::findByCodeCached('basic');
        $this->assertSame('Basic', $planFresh->name);
    }
}
