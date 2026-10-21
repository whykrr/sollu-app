<?php

namespace App\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Enums\SubscriptionStatus;
use App\Events\Invoice\InvoicePaid;
use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Notifications\OutletActivatedNotification;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\App\Notification\NotificationDispatcherService;
use App\Services\App\Outlet\ManageOutletStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompleteInvoiceService
{
    /**
     * Completes an invoice payment, setting its status to paid
     * and activating the latest associated subscription if applicable.
     */
    public function execute(Invoice $invoice): Invoice
    {
        if ($invoice->status === Status::Paid || (is_string($invoice->status) && $invoice->status === Status::Paid->value)) {
            return $invoice;
        }

        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($invoice): Invoice {
            $now = Carbon::now();

            // Mark invoice as paid
            $invoice->update([
                'status' => Status::Paid,
                'paid_at' => $now,
            ]);

            InvoicePaid::dispatch($invoice);

            $business = $invoice->business;

            // Check if this invoice is an outlet addition
            $isOutletAddition = false;
            $isPlanRenewal = false;
            if ($invoice->items()->where('item_type', 'outlet_addition')->exists()) {
                $isOutletAddition = true;
            }
            if ($invoice->items()->where('item_type', 'plan_renewal')->exists()) {
                $isPlanRenewal = true;
            }

            // Resolve subscription associated with this invoice (or fallback to latest)
            $subscription = null;
            $subscriptionItem = $invoice->items()->whereIn('item_type', ['recurring_plan', 'plan_renewal'])->first();
            if ($subscriptionItem && isset($subscriptionItem->metadata['subscription_id'])) {
                $subscription = $business->subscriptions()->find($subscriptionItem->metadata['subscription_id']);
            }
            if (! $subscription) {
                $subscription = $business->subscriptions()->latest()->first();
            }

            if ($isPlanRenewal) {
                $renewalItem = $invoice->items()->where('item_type', 'plan_renewal')->first();
                $subscriptionId = $renewalItem->metadata['subscription_id'] ?? null;
                if ($subscriptionId) {
                    $targetSub = $business->subscriptions()->find($subscriptionId);
                    if ($targetSub) {
                        // Perpanjang expired_at sesuai billing_cycle (365 atau 30 hari dari expired_at yang ada)
                        // Jika sudah lewat expired_at, mulai dari waktu sekarang
                        $daysToAdd = $targetSub->billing_cycle === 'yearly' ? 365 : 30;
                        $baseDate = $targetSub->expired_at && $targetSub->expired_at->isFuture()
                            ? $targetSub->expired_at
                            : $now;

                        $targetSub->update([
                            'expired_at' => $baseDate->copy()->addDays($daysToAdd),
                            'status' => SubscriptionStatus::Active,
                        ]);

                        try {
                            app(NotificationDispatcherService::class)->sendToRootUser(
                                $business,
                                new SubscriptionActivatedNotification(
                                    $business,
                                    $targetSub->plan,
                                    $targetSub->expired_at->translatedFormat('d F Y')
                                )
                            );
                        } catch (\Exception $e) {
                            Log::error('Gagal mengirim notifikasi perpanjangan langganan: '.$e->getMessage());
                        }
                    }
                }
            } elseif ($subscription && ! $isOutletAddition) {
                // Cancel any previous active subscriptions before activating the new one
                $business->subscriptions()
                    ->where('id', '!=', $subscription->id)
                    ->where('status', SubscriptionStatus::Active->value)
                    ->update([
                        'status' => SubscriptionStatus::Canceled->value,
                        'canceled_at' => $now,
                    ]);

                if ($subscription->status !== SubscriptionStatus::Active) {
                    $subscription->update([
                        'status' => SubscriptionStatus::Active,
                    ]);
                }

                // Send notification to the business root user
                try {
                    $expiredAt = $subscription->expired_at
                        ? $subscription->expired_at->translatedFormat('d F Y')
                        : 'Lifetime';

                    app(NotificationDispatcherService::class)->sendToRootUser(
                        $business,
                        new SubscriptionActivatedNotification(
                            $business,
                            $subscription->plan,
                            $expiredAt
                        )
                    );
                } catch (\Exception $e) {
                    Log::error('Gagal mengirim notifikasi aktivasi langganan: '.$e->getMessage());
                }
            }

            if ($isOutletAddition) {
                $outletAdditionItem = $invoice->items()->where('item_type', 'outlet_addition')->first();
                if ($outletAdditionItem && isset($outletAdditionItem->metadata['outlet_id'])) {
                    $outlet = Outlet::find($outletAdditionItem->metadata['outlet_id']);
                    if ($outlet && ! $outlet->is_active) {
                        $owner = $business->users()->where('is_root_user', true)->first() ?? $business->users()->first();
                        app(ManageOutletStatusService::class)->toggleStatus($outlet, true, $owner);

                        if ($owner) {
                            try {
                                app(NotificationDispatcherService::class)->sendToRootUser(
                                    $business,
                                    new OutletActivatedNotification($outlet)
                                );
                            } catch (\Exception $e) {
                                Log::error('Gagal mengirim notifikasi aktivasi outlet: '.$e->getMessage());
                            }
                        }
                    }
                }
            }

            // Clear cached subscription and user summary for tenant
            Business::clearCache($business->id);
            $rootUser = $business->users()->where('is_root_user', true)->first();
            if ($rootUser) {
                SummaryUser::cacheDelete($rootUser->id);
            }

            return $invoice;
        });

        return $invoice;
    }
}
