<?php

namespace Tests\Integration\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function createBusiness(): Business
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        return Business::create([
            'name' => 'Test Retail',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);
    }

    public function test_is_trial_cast_and_scope_trial(): void
    {
        $business = $this->createBusiness();
        $plan = SubscriptionPlan::factory()->create([
            'code' => 'micro',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 29000,
            'is_active' => true,
        ]);

        $trialSub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addDays(14),
        ]);

        $paidSub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertTrue($trialSub->is_trial);
        $this->assertFalse($paidSub->is_trial);

        $trialSubscriptions = Subscription::trial()->get();
        $this->assertTrue($trialSubscriptions->contains('id', $trialSub->id));
        $this->assertFalse($trialSubscriptions->contains('id', $paidSub->id));
    }

    public function test_active_subscription_with_expiration_dates(): void
    {
        $business = $this->createBusiness();
        $plan = SubscriptionPlan::factory()->create([
            'code' => 'basic',
            'name' => 'Paket Basic',
            'price_per_outlet' => 99000,
            'is_active' => true,
        ]);

        $expiredSub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => Carbon::now()->subDays(40),
            'expired_at' => Carbon::now()->subDays(10),
        ]);

        $this->assertNull($business->getActiveSubscriptionWithPlan());

        Business::clearActiveSubscriptionCache($business->id);

        $futureSub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => Carbon::now(),
            'expired_at' => Carbon::now()->addDays(30),
        ]);

        $active = $business->getActiveSubscriptionWithPlan();
        $this->assertNotNull($active);
        $this->assertSame($futureSub->id, $active->id);
    }
}
