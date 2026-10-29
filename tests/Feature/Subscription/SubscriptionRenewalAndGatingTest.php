<?php

namespace Tests\Feature\Subscription;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionRenewalNotification;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionRenewalAndGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function createMerchantWithUsers(string $typeCode = 'retail'): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => $typeCode],
            [
                'name' => ucfirst($typeCode),
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::POS->value,
                    FeatureEnum::SHIFT_CASHIER->value,
                    FeatureEnum::PRODUCT_CATALOG->value,
                    FeatureEnum::FEATURE_PERSONALIZATION->value,
                ],
            ]
        );

        $business = Business::create([
            'name' => 'Gating Test Business',
            'owner_name' => 'Gating Owner',
            'email' => 'gating_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $rootUser = User::create([
            'business_id' => $business->id,
            'name' => 'Root Owner User',
            'email' => 'root_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $staffUser = User::create([
            'business_id' => $business->id,
            'name' => 'Staff Cashier',
            'email' => 'staff_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => false,
        ]);

        return [$business, $rootUser, $staffUser];
    }

    public function test_three_tier_feature_hierarchy_intersection(): void
    {
        [$business] = $this->createMerchantWithUsers();

        // Level 2: Basic plan has multiple features including POS_CASHIER, TRANSACTION, PRODUCT_CATALOG, CUSTOMER_MANAGEMENT, etc.
        $plan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now()->subDay(),
            'expired_at' => now()->addDays(29),
        ]);

        // Level 3: Personalization sets active features.
        // It includes POS (allowed by L1 and L2), and CRM (allowed by L2, but NOT in L1 retail features above)
        $business->update([
            'settings' => [
                'active_features' => [
                    FeatureEnum::POS->value,
                    FeatureEnum::CRM->value,
                ],
            ],
        ]);
        $business->clearMemoizedFeatures();

        $activeFeatures = $business->activePlanFeatures();

        // Should include POS (in L1, L2, and L3)
        $this->assertContains(FeatureEnum::POS, $activeFeatures);

        // Should NOT include CRM because it is NOT allowed by Level 1 (BusinessType)
        $this->assertNotContains(FeatureEnum::CRM, $activeFeatures);

        // Should NOT include SHIFT_CASHIER because it was excluded by Level 3 personalization
        $this->assertNotContains(FeatureEnum::SHIFT_CASHIER, $activeFeatures);
    }

    public function test_three_day_grace_period_for_paid_subscription(): void
    {
        [$business] = $this->createMerchantWithUsers();
        $plan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();

        // Expired 1 day ago (within 3-day grace period)
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now()->subDays(31),
            'expired_at' => now()->subDay(),
        ]);

        $business->clearMemoizedFeatures();

        $this->assertTrue($business->isInGracePeriod());
        $this->assertEquals(2, $business->graceDaysRemaining());
        $this->assertNotNull($business->getActiveSubscriptionWithPlan());
        $this->assertEquals($subscription->id, $business->getActiveSubscriptionWithPlan()->id);

        // Trial expired 1 day ago should NOT get grace period
        $subscription->update(['is_trial' => true]);
        $business->clearMemoizedFeatures();

        $this->assertFalse($business->isInGracePeriod());
        $this->assertEquals(0, $business->graceDaysRemaining());
        $this->assertNull($business->getActiveSubscriptionWithPlan());
    }

    public function test_renewal_notification_command_dispatches_only_to_root_user(): void
    {
        Notification::fake();

        [$business, $rootUser, $staffUser] = $this->createMerchantWithUsers();
        $plan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();

        // Subscription expiring in 3 days (trigger interval H-3)
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now()->subDays(27),
            'expired_at' => Carbon::now()->addDays(3)->startOfDay(),
        ]);

        $this->artisan('subscription:renewal-notification')
            ->assertSuccessful();

        // Assert notification sent to Root User
        Notification::assertSentTo(
            $rootUser,
            SubscriptionRenewalNotification::class,
            function ($notification) {
                return $notification->daysRemaining === 3 && ! $notification->isTrial;
            }
        );

        // Assert notification was NOT sent to staff/non-root user
        Notification::assertNotSentTo(
            $staffUser,
            SubscriptionRenewalNotification::class
        );
    }

    public function test_renewal_notification_auto_suspends_subscriptions_past_grace_period(): void
    {
        Notification::fake();

        [$business] = $this->createMerchantWithUsers();
        $plan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();

        // Expired 4 days ago (past the 3-day grace period)
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now()->subDays(35),
            'expired_at' => Carbon::now()->subDays(4)->startOfDay(),
        ]);

        $this->artisan('subscription:renewal-notification')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Suspended, $subscription->status);
    }
}
