<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Pos\PosSettingsPayloadBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSettingsPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected PosSettingsPayloadBuilder $builder;

    protected Business $business;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'POS Test Merchant',
            'owner_name' => 'Owner',
            'email' => 'payload_builder_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Sentral',
            'address' => 'Jl. Jenderal Sudirman No. 10',
            'phone' => '081299998888',
            'email' => 'outlet_sentral@test.test',
            'is_active' => true,
        ]);
        $this->outlet->setRelation('business', $this->business);

        $this->builder = app(PosSettingsPayloadBuilder::class);
    }

    public function test_build_structured_settings_includes_tax_service_and_rounding_configurations(): void
    {
        $outletSettings = collect([
            (object) ['category' => 'financial', 'key' => 'tax', 'value' => 11.0],
            (object) ['category' => 'financial', 'key' => 'service_fee', 'value' => 5.0],
            (object) ['category' => 'financial', 'key' => 'tax_included_in_price', 'value' => 'true'],
            (object) ['category' => 'financial', 'key' => 'rounding_enabled', 'value' => 'true'],
            (object) ['category' => 'financial', 'key' => 'rounding_mode', 'value' => 'nearest'],
            (object) ['category' => 'pos', 'key' => 'allow_negative_stock', 'value' => 'true'],
            (object) ['category' => 'pos', 'key' => 'enable_supervisor_pin', 'value' => 'true'],
            (object) ['category' => 'pos', 'key' => 'pos_order_types', 'value' => ['walk_in', 'take_away']],
        ]);

        $result = $this->builder->buildStructuredSettings($this->outlet, $outletSettings);

        $this->assertIsArray($result);
        $this->assertEquals(11.0, $result['tax_percentage']);
        $this->assertEquals(5.0, $result['service_charge_percentage']);
        $this->assertTrue($result['tax_included_in_price']);
        $this->assertTrue($result['rounding_enabled']);
        $this->assertEquals('nearest', $result['rounding_mode']);
        $this->assertTrue($result['allow_negative_stock']);
        $this->assertEquals(['walk_in', 'take_away'], $result['pos_order_types']);
        $this->assertArrayHasKey('receipt', $result);
    }

    public function test_enable_supervisor_pin_strictly_gated_by_double_authorization_feature(): void
    {
        $outletSettings = collect([
            (object) ['category' => 'pos', 'key' => 'enable_supervisor_pin', 'value' => 'true'],
        ]);

        // Scenario A: Merchant business DOES have DOUBLE_AUTHORIZATION feature
        $this->business->update([
            'settings' => [
                'active_features' => [
                    FeatureEnum::FEATURE_PERSONALIZATION->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
                    FeatureEnum::POS->value,
                ],
            ],
        ]);
        $this->business->clearMemoizedFeatures();
        $this->outlet->setRelation('business', $this->business);

        $resultWithFeature = $this->builder->buildStructuredSettings($this->outlet, $outletSettings);
        $this->assertTrue($resultWithFeature['enable_supervisor_pin']);

        // Scenario B: Merchant business DOES NOT have DOUBLE_AUTHORIZATION feature
        $this->business->update([
            'settings' => [
                'active_features' => [
                    FeatureEnum::FEATURE_PERSONALIZATION->value,
                    FeatureEnum::POS->value,
                ],
            ],
        ]);
        $this->business->clearMemoizedFeatures();
        $this->outlet->setRelation('business', $this->business);

        $resultWithoutFeature = $this->builder->buildStructuredSettings($this->outlet, $outletSettings);
        $this->assertFalse(
            $resultWithoutFeature['enable_supervisor_pin'],
            'enable_supervisor_pin must evaluate to false when DOUBLE_AUTHORIZATION is inactive'
        );
    }

    public function test_build_structured_settings_exposes_active_features_from_business_plan(): void
    {
        $outletSettings = collect([]);

        $this->business->update([
            'settings' => [
                'active_features' => [
                    FeatureEnum::FEATURE_PERSONALIZATION->value,
                    FeatureEnum::POS->value,
                    FeatureEnum::SHIFT_CASHIER->value,
                    FeatureEnum::SPLIT_PAYMENT->value,
                    FeatureEnum::CRM->value,
                ],
            ],
        ]);
        $this->business->clearMemoizedFeatures();
        $this->outlet->setRelation('business', $this->business);

        $result = $this->builder->buildStructuredSettings($this->outlet, $outletSettings);

        $this->assertArrayHasKey('active_features', $result, 'Payload must expose active_features array');
        $this->assertIsArray($result['active_features']);
        $this->assertContains('pos', $result['active_features']);
        $this->assertContains('shift_cashier', $result['active_features']);
        $this->assertContains('split_payment', $result['active_features']);
        $this->assertContains('crm', $result['active_features']);
    }

    public function test_cash_drawer_feature_enum_is_registered_and_exposed(): void
    {
        $hasCashDrawerEnum = defined(FeatureEnum::class.'::CASH_DRAWER');
        $this->assertTrue($hasCashDrawerEnum, 'FeatureEnum::CASH_DRAWER must be registered in enum cases');

        if ($hasCashDrawerEnum) {
            $this->business->update([
                'settings' => [
                    'active_features' => [
                        FeatureEnum::FEATURE_PERSONALIZATION->value,
                        FeatureEnum::POS->value,
                        FeatureEnum::CASH_DRAWER->value,
                    ],
                ],
            ]);
            $this->business->clearMemoizedFeatures();

            $result = $this->builder->buildStructuredSettings($this->outlet, collect([]));
            $this->assertArrayHasKey('active_features', $result);
            $this->assertContains('cash_drawer', $result['active_features']);
        }
    }

    public function test_build_structured_settings_returns_empty_active_features_when_outlet_has_no_business(): void
    {
        $orphanOutlet = new Outlet([
            'id' => '0192e20e-6f81-7d12-bf91-45a709081234',
            'name' => 'Outlet Tanpa Business',
        ]);
        $orphanOutlet->setRelation('business', null);

        $result = $this->builder->buildStructuredSettings($orphanOutlet, collect([]));

        $this->assertArrayHasKey('active_features', $result);
        $this->assertSame([], $result['active_features']);
    }

    public function test_build_structured_settings_ensures_active_features_is_sequential_list(): void
    {
        $result = $this->builder->buildStructuredSettings($this->outlet, collect([]));

        $this->assertIsArray($result['active_features']);
        $this->assertTrue(array_is_list($result['active_features']), 'active_features must be a 0-indexed sequential list');
    }

    public function test_build_outlet_profile_returns_correct_shape_and_initial(): void
    {
        $profile = $this->builder->buildOutletProfile($this->outlet);

        $this->assertIsArray($profile);
        $this->assertEquals($this->outlet->id, $profile['id']);
        $this->assertEquals('Outlet Sentral', $profile['name']);
        $this->assertNotEmpty($profile['initial']);
        $this->assertEquals('Jl. Jenderal Sudirman No. 10', $profile['address']);
        $this->assertEquals('081299998888', $profile['phone']);
        $this->assertEquals('outlet_sentral@test.test', $profile['email']);
    }
}
