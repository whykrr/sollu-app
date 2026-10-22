<?php

namespace Tests\Feature\Console;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionRenewalNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendSubscriptionRenewalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_notification_for_expiring_subscriptions()
    {
        Notification::fake();

        $plan = SubscriptionPlan::factory()->create();
        $type = BusinessType::create(['name' => 'F&B', 'code' => 'fnb_iso']);
        $business = Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant@test.com',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create(['business_id' => $business->id]);

        // Expiring in 7 days
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(23),
            'expired_at' => Carbon::now()->addDays(7)->startOfDay(),
        ]);

        $this->artisan('subscription:renewal-notification')->assertSuccessful();

        Notification::assertSentTo(
            $owner,
            SubscriptionRenewalNotification::class,
            function ($notification) use ($business, $plan) {
                return $notification->business->id === $business->id &&
                       $notification->plan->id === $plan->id &&
                       $notification->daysRemaining === 7;
            }
        );
    }
}
