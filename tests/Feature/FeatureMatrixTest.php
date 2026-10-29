<?php

namespace Tests\Feature;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\App\Master\ProductService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BusinessType::clearCache();
    }

    protected function getOrCreateFeature(FeatureEnum $enum, string $name): Feature
    {
        return Feature::firstOrCreate(
            ['code' => $enum->value],
            [
                'name' => $name,
                'is_active' => true,
                'module' => 'general',
                'group' => 'general',
                'group_label' => 'Umum',
                'sort_order' => 1,
            ]
        );
    }

    public function test_summary_user_contains_feature_matrix_and_unpersonalized_state(): void
    {
        $posFeature = $this->getOrCreateFeature(FeatureEnum::POS, 'POS Kasir');
        $this->getOrCreateFeature(FeatureEnum::INVENTORY_MANAGEMENT, 'Inventori');

        $microPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::MICRO->value,
            'name' => 'Micro Plan',
            'price_per_outlet' => 59000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $microPlan->systemFeatures()->sync([$posFeature->id]);
        $microPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS->value, FeatureEnum::INVENTORY_MANAGEMENT->value],
        ]);
        BusinessType::clearCache();

        $business = Business::create([
            'name' => 'Toko Mikro',
            'owner_name' => 'Pemilik Mikro',
            'email' => 'mikro@example.com',
            'phone' => '081299900001',
            'business_type_id' => $businessType->id,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $microPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
            'is_root_user' => true,
        ]);

        $summary = SummaryUser::make($user)->cached();

        $this->assertArrayHasKey('features', $summary);
        $this->assertArrayHasKey('feature_matrix', $summary);

        $matrix = $summary['feature_matrix'];
        $this->assertFalse($matrix['is_personalized']);
        $this->assertContains(FeatureEnum::POS->value, $matrix['active']);
        $this->assertNotContains(FeatureEnum::INVENTORY_MANAGEMENT->value, $matrix['active']);

        $this->assertContains(FeatureEnum::POS->value, $matrix['type_features']);
        $this->assertContains(FeatureEnum::INVENTORY_MANAGEMENT->value, $matrix['type_features']);

        $this->assertContains(FeatureEnum::POS->value, $matrix['plan_features']);
        $this->assertNotContains(FeatureEnum::INVENTORY_MANAGEMENT->value, $matrix['plan_features']);
    }

    public function test_summary_user_contains_personalized_state_when_configured(): void
    {
        $posFeature = $this->getOrCreateFeature(FeatureEnum::POS, 'POS Kasir');
        $personalizationFeature = $this->getOrCreateFeature(FeatureEnum::FEATURE_PERSONALIZATION, 'Personalisasi Fitur');
        $invFeature = $this->getOrCreateFeature(FeatureEnum::INVENTORY_MANAGEMENT, 'Inventori');

        $basicPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::BASIC->value,
            'name' => 'Basic Plan',
            'price_per_outlet' => 129000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $basicPlan->systemFeatures()->sync([$posFeature->id, $personalizationFeature->id, $invFeature->id]);
        $basicPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [
                FeatureEnum::POS->value,
                FeatureEnum::FEATURE_PERSONALIZATION->value,
                FeatureEnum::INVENTORY_MANAGEMENT->value,
            ],
        ]);
        BusinessType::clearCache();

        $business = Business::create([
            'name' => 'Toko Basic',
            'owner_name' => 'Pemilik Basic',
            'email' => 'basic@example.com',
            'phone' => '081299900002',
            'business_type_id' => $businessType->id,
            'settings' => [
                // Merchant disabled INVENTORY_MANAGEMENT in personalization
                'active_features' => [
                    FeatureEnum::POS->value,
                    FeatureEnum::FEATURE_PERSONALIZATION->value,
                ],
            ],
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
            'is_root_user' => true,
        ]);

        $summary = SummaryUser::make($user)->cached();
        $matrix = $summary['feature_matrix'];

        $this->assertTrue($matrix['is_personalized']);
        $this->assertContains(FeatureEnum::POS->value, $matrix['active']);
        // Inventory was excluded in merchant settings, so it's not active
        $this->assertNotContains(FeatureEnum::INVENTORY_MANAGEMENT->value, $matrix['active']);
    }

    public function test_product_service_gates_track_inventory_based_on_feature(): void
    {
        $posFeature = $this->getOrCreateFeature(FeatureEnum::POS, 'POS Kasir');

        $microPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::MICRO->value,
            'name' => 'Micro Plan',
            'price_per_outlet' => 59000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $microPlan->systemFeatures()->sync([$posFeature->id]);
        $microPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
        ]);
        BusinessType::clearCache();

        $business = Business::create([
            'name' => 'Toko No Inventory',
            'owner_name' => 'Owner',
            'email' => 'noinv@example.com',
            'phone' => '081299900003',
            'business_type_id' => $businessType->id,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $microPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addMonth(),
        ]);

        $service = app(ProductService::class);

        // Attempt to create product with track_inventory = true on micro plan
        $product = $service->createProduct([
            'business_id' => $business->id,
            'name' => 'Barang Mikro',
            'track_inventory' => true,
            'base_price' => 10000,
        ]);

        // track_inventory must be false because Micro plan does not have INVENTORY_MANAGEMENT
        $this->assertFalse((bool) $product->track_inventory);
    }
}
