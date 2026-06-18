<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Subscribe business to a plan
     */
    public function subscribe(Business $business, SubscriptionPlan $plan, string $billing_cycle = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($business, $plan, $billing_cycle) {
            // Cancel current active subscription if any
            $current = $business->subscriptions()->where('status', 'active')->first();
            if ($current) {
                $current->update([
                    'status' => 'canceled',
                    'canceled_at' => Carbon::now()
                ]);
            }

            $durationDays = $billing_cycle === 'yearly' ? 365 : 30;

            $subscription = Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => 'inactive',
                'billing_cycle' => $billing_cycle,
                'started_at' => Carbon::now(),
                'expired_at' => Carbon::now()->addDays($durationDays),
            ]);

            // Sync all active outlets to the new subscription
            $activeOutlets = $business->outlets()->where('is_active', true)->get();
            foreach ($activeOutlets as $outlet) {
                $subscription->subscriptionOutlets()->create([
                    'outlet_id' => $outlet->id,
                    'activated_at' => Carbon::now(),
                ]);
            }

            return $subscription;
        });
    }

    /**
     * Cancel a subscription
     */
    public function cancel(Subscription $subscription): bool
    {
        return $subscription->update([
            'status' => 'canceled',
            'canceled_at' => Carbon::now(),
        ]);
    }
}
