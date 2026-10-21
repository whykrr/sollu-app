<?php

namespace Tests\Integration\Services\App;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\App\Subscription\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService;
    }

    public function test_it_subscribes_business_to_plan()
    {
        $this->seed(DatabaseSeeder::class);
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'test@test.test',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        // Create an existing active subscription
        $plan1 = SubscriptionPlan::first();
        $currentSubscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan1->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(10),
            'expired_at' => Carbon::now()->addDays(20),
        ]);

        $outlet1 = Outlet::create(['business_id' => $business->id, 'name' => 'Outlet 1', 'is_active' => true]);
        $outlet2 = Outlet::create(['business_id' => $business->id, 'name' => 'Outlet 2', 'is_active' => false]);

        $plan2 = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'code' => 'pro2',
            'price_per_outlet' => 100000,
            'yearly_discount_percent' => 10,
        ]);

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);

        $newSubscription = $this->service->subscribe($business, $plan2, 'yearly');

        $this->assertInstanceOf(Subscription::class, $newSubscription);
        $this->assertEquals($plan2->id, $newSubscription->plan_id);
        $this->assertEquals(SubscriptionStatus::Pending, $newSubscription->status);
        $this->assertEquals('yearly', $newSubscription->billing_cycle);
        $this->assertEquals($now, $newSubscription->started_at);
        $this->assertEquals($now->copy()->addDays(365), $newSubscription->expired_at);

        $activeCount = $business->outlets()->where('is_active', true)->count();
        $this->assertCount($activeCount, $newSubscription->subscriptionOutlets);

        // Check old subscription remains active
        $currentSubscription->refresh();
        $this->assertEquals(SubscriptionStatus::Active, $currentSubscription->status);
        $this->assertNull($currentSubscription->canceled_at);
    }

    public function test_it_cancels_subscription()
    {
        $this->seed(DatabaseSeeder::class);
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business 2',
            'owner_name' => 'Owner',
            'email' => 'test2@test.test',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);
        $plan = SubscriptionPlan::first();

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(10),
            'expired_at' => Carbon::now()->addDays(20),
        ]);

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);

        $result = $this->service->cancel($subscription);

        $this->assertTrue($result);
        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Canceled, $subscription->status);
        $this->assertEquals($now, $subscription->canceled_at);
    }

    public function test_it_eliminates_active_trial_immediately_when_merchant_subscribes_to_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Trial Merchant Business',
            'owner_name' => 'Owner Trial',
            'email' => 'trial_owner@test.test',
            'phone' => '08123456788',
            'status' => 'active',
            'has_used_trial' => true,
            'business_type_id' => $type->id,
        ]);

        $trialPlan = SubscriptionPlan::first();

        // Active trial subscription with remaining days
        $trialSubscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $trialPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => Carbon::now()->subDays(2),
            'expired_at' => Carbon::now()->addDays(12),
        ]);

        Outlet::create(['business_id' => $business->id, 'name' => 'Outlet Main', 'is_active' => true]);

        $paidPlan = SubscriptionPlan::create([
            'name' => 'Paid Micro Plan',
            'code' => 'micro_paid_svc',
            'price_per_outlet' => 59000,
            'yearly_discount_percent' => 0,
        ]);

        $newSubscription = $this->service->subscribe($business, $paidPlan, 'monthly');

        // Verify trial was canceled immediately
        $trialSubscription->refresh();
        $this->assertEquals(SubscriptionStatus::Canceled, $trialSubscription->status);
        $this->assertNotNull($trialSubscription->canceled_at);

        // Verify new subscription is NOT trial and pending
        $this->assertInstanceOf(Subscription::class, $newSubscription);
        $this->assertFalse($newSubscription->is_trial);
        $this->assertEquals(SubscriptionStatus::Pending, $newSubscription->status);
        $this->assertTrue($business->fresh()->has_used_trial);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
