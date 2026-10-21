<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalNotification;
use App\Services\App\Notification\NotificationDispatcherService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionRenewalNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:renewal-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription renewal notifications (H-14, H-7, H-3, H-1, H-0, D+1) and suspend expired subscriptions past grace period';

    /**
     * Execute the console command.
     */
    public function handle(NotificationDispatcherService $dispatcher)
    {
        $this->info('Starting subscription renewal notification process...');

        $intervals = [14, 7, 3, 1, 0, -1];
        $count = 0;

        foreach ($intervals as $days) {
            $targetDate = Carbon::now()->addDays($days)->startOfDay();

            $subscriptions = Subscription::with(['business.users', 'plan'])
                ->where('status', SubscriptionStatus::Active)
                ->whereDate('expired_at', $targetDate)
                ->get();

            foreach ($subscriptions as $subscription) {
                if (! $subscription->business || ! $subscription->plan) {
                    continue;
                }

                try {
                    $expiredAtFormatted = $subscription->expired_at->translatedFormat('d F Y');
                    $dispatcher->sendToRootUser(
                        $subscription->business,
                        new SubscriptionRenewalNotification(
                            $subscription->business,
                            $subscription->plan,
                            $expiredAtFormatted,
                            $days,
                            (bool) $subscription->is_trial
                        )
                    );
                    $this->info("Notification sent for business: {$subscription->business->name} (Days left: {$days})");
                    $count++;
                } catch (\Exception $e) {
                    Log::error('Failed to send renewal notification: '.$e->getMessage());
                }
            }
        }

        // Suspend subscriptions that have exceeded the 3-day grace period
        $overdueCutoff = Carbon::now()->subDays(3);
        $expiredSubscriptions = Subscription::where('status', SubscriptionStatus::Active)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', $overdueCutoff)
            ->get();

        $suspendedCount = 0;
        foreach ($expiredSubscriptions as $sub) {
            $sub->update(['status' => SubscriptionStatus::Suspended]);
            Business::clearCache($sub->business_id);
            $suspendedCount++;
        }

        if ($suspendedCount > 0) {
            $this->info("Suspended {$suspendedCount} overdue subscriptions past grace period.");
        }

        $this->info("Successfully sent {$count} renewal notifications.");
    }
}
