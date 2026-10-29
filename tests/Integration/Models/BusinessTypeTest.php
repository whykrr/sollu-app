<?php

namespace Tests\Integration\Models;

use App\Enums\FeatureEnum;
use App\Models\BusinessType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_retrieves_dynamic_business_type(): void
    {
        $businessType = BusinessType::factory()->create([
            'code' => 'restaurant',
            'name' => 'Restoran',
            'is_visible' => true,
            'sort_order' => 10,
            'features' => [FeatureEnum::PRODUCT_CATALOG->value, FeatureEnum::POS->value],
        ]);

        $this->assertSame('restaurant', $businessType->code);
        $this->assertSame('Restoran', $businessType->name);
        $this->assertTrue($businessType->is_visible);
        $this->assertSame(10, $businessType->sort_order);
    }

    public function test_has_feature_and_feature_enums_methods(): void
    {
        $businessType = BusinessType::factory()->create([
            'code' => 'coffee_shop',
            'features' => [
                FeatureEnum::PRODUCT_CATALOG->value,
                FeatureEnum::POS->value,
            ],
        ]);

        $this->assertTrue($businessType->hasFeature(FeatureEnum::PRODUCT_CATALOG));
        $this->assertTrue($businessType->hasFeature(FeatureEnum::POS));
        $this->assertFalse($businessType->hasFeature(FeatureEnum::STOCK_ADJUSTMENTS));

        $featureEnums = $businessType->featureEnums();
        $this->assertCount(2, $featureEnums);
        $this->assertContains(FeatureEnum::PRODUCT_CATALOG, $featureEnums);
        $this->assertContains(FeatureEnum::POS, $featureEnums);
    }

    public function test_options_helper(): void
    {
        BusinessType::factory()->create([
            'code' => 'minimarket',
            'name' => 'Minimarket',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        BusinessType::factory()->create([
            'code' => 'coffee_shop',
            'name' => 'Coffee Shop',
            'is_visible' => true,
            'sort_order' => 2,
        ]);

        $options = BusinessType::options();
        $this->assertArrayHasKey('minimarket', $options);
        $this->assertSame('Minimarket', $options['minimarket']);
    }

    public function test_get_all_cached_and_cache_invalidation(): void
    {
        BusinessType::factory()->create(['code' => 'minimarket']);

        $cached = BusinessType::getAllCached();
        $this->assertCount(1, $cached);

        BusinessType::factory()->create(['code' => 'coffee_shop']);
        // Cache should have been invalidated automatically on create
        $fresh = BusinessType::getAllCached();
        $this->assertCount(2, $fresh);
    }
}
