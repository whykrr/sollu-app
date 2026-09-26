<?php

namespace Tests\Feature\Middleware;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'plan.feature:'.FeatureEnum::POS_CASHIER->value])
            ->get('/test-pos-cashier', function () {
                return response()->json(['status' => 'success']);
            });

        Route::middleware(['web', 'auth', 'plan.feature:'.FeatureEnum::RECIPE_MANAGEMENT->value])
            ->get('/test-recipe-management', function () {
                return response()->json(['status' => 'success']);
            });
    }

    public function test_check_plan_feature_allows_access_via_summary_cache_without_database_queries(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $proPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::PRO->value,
            'name' => 'Pro Plan',
            'price_per_outlet' => 100000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $proPlan->systemFeatures()->attach([$posFeature->id]);
        $proPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Store Pro',
            'owner_name' => 'Store Owner',
            'email' => 'prostore@example.com',
            'phone' => '081299990003',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDays(1),
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
        ]);

        // Pre-warm SummaryUser cache (simulating normal login / first page visit)
        SummaryUser::make($user)->cached();

        // Run request and track queries
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->getJson('/test-pos-cashier');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $queries = DB::getQueryLog();
        $businessOrSubscriptionQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], '"businesses"') || str_contains($query['query'], '"subscriptions"');
        });

        // There should be NO database queries to businesses or subscriptions tables on cache hit
        $this->assertCount(0, $businessOrSubscriptionQueries);
    }

    public function test_check_plan_feature_fallback_via_cached_business(): void
    {
        $posFeature = Feature::factory()->create([
            'code' => FeatureEnum::POS_CASHIER->value,
            'name' => 'POS Kasir',
            'is_active' => true,
        ]);

        $proPlan = SubscriptionPlan::factory()->create([
            'code' => PlanEnum::PRO->value,
            'name' => 'Pro Plan',
            'price_per_outlet' => 100000,
            'is_active' => true,
            'is_public' => true,
        ]);
        $proPlan->systemFeatures()->attach([$posFeature->id]);
        $proPlan->clearFeatureCache();

        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Store Pro Fallback',
            'owner_name' => 'Store Owner',
            'email' => 'prostorefb@example.com',
            'phone' => '081299990005',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDays(1),
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
        ]);

        // Explicitly clear user summary cache to test fallback path
        SummaryUser::cacheDelete($user->id);

        $response = $this->actingAs($user)->getJson('/test-pos-cashier');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_check_plan_feature_rejects_locked_features(): void
    {
        $businessType = BusinessType::factory()->create([
            'code' => 'retail',
            'features' => [FeatureEnum::POS_CASHIER->value],
        ]);

        $business = Business::create([
            'name' => 'Basic Store',
            'owner_name' => 'Basic Owner',
            'email' => 'basic@example.com',
            'phone' => '081299990004',
            'business_type_id' => $businessType->id,
            'trial_end_at' => Carbon::now()->subDays(1),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($user)->getJson('/test-recipe-management');

        $response->assertStatus(403)
            ->assertJson([
                'is_feature_locked' => true,
                'feature' => FeatureEnum::RECIPE_MANAGEMENT->value,
            ]);
    }
}
