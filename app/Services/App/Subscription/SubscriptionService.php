<?php

namespace App\Services\App\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Subscribe business to a plan
     *
     * @param  array<string>|null  $selectedOutletIds
     */
    public function subscribe(Business $business, SubscriptionPlan $plan, string $billing_cycle = 'monthly', ?array $selectedOutletIds = null): Subscription
    {
        /** @var Subscription */
        $subscription = DB::transaction(function () use ($business, $plan, $billing_cycle, $selectedOutletIds) {
            if (! $business->has_used_trial) {
                $business->update(['has_used_trial' => true]);
            }

            $durationDays = $billing_cycle === 'yearly' ? 365 : 30;

            $subscription = Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Pending,
                'billing_cycle' => $billing_cycle,
                'is_trial' => false,
                'started_at' => Carbon::now(),
                'expired_at' => Carbon::now()->addDays($durationDays),
            ]);

            // If specific outlets were selected (e.g. during a downgrade conflict resolution)
            if ($selectedOutletIds !== null && count($selectedOutletIds) > 0) {
                // Deactivate unselected outlets
                $business->outlets()
                    ->where('is_active', true)
                    ->whereNotIn('id', $selectedOutletIds)
                    ->update(['is_active' => false]);

                // Deactivate unselected outlets on existing active subscriptions
                $activeSub = $business->subscriptions()->where('status', SubscriptionStatus::Active)->first();
                if ($activeSub) {
                    $activeSub->subscriptionOutlets()
                        ->whereNotIn('outlet_id', $selectedOutletIds)
                        ->whereNull('deactivated_at')
                        ->update(['deactivated_at' => Carbon::now()]);
                }

                $outletsToAttach = $business->outlets()->whereIn('id', $selectedOutletIds)->get();
            } else {
                $outletsToAttach = $business->outlets()->where('is_active', true)->get();
            }

            foreach ($outletsToAttach as $outlet) {
                $subscription->subscriptionOutlets()->create([
                    'outlet_id' => $outlet->id,
                    'activated_at' => Carbon::now(),
                ]);
            }

            Business::clearActiveSubscriptionCache($business->id);

            return $subscription;
        });

        return $subscription;
    }

    /**
     * Cancel a subscription
     */
    public function cancel(Subscription $subscription): bool
    {
        return $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => Carbon::now(),
        ]);
    }
}
