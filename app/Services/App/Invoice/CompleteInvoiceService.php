<?php

namespace App\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Notifications\SubscriptionActivatedNotification;
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

            \App\Events\Invoice\InvoicePaid::dispatch($invoice);

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

            // Only activate the subscription if it is a recurring plan, OR if the subscription isn't active yet.
            // Generally, we just ensure the latest subscription is set to active.
            $subscription = $business->subscriptions()->latest()->first();

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

                        $owner = $business->users()->first();
                        if ($owner) {
                            try {
                                $owner->notify(new SubscriptionActivatedNotification(
                                    $business,
                                    $targetSub->plan,
                                    $targetSub->expired_at->translatedFormat('d F Y')
                                ));
                            } catch (\Exception $e) {
                                Log::error('Gagal mengirim notifikasi perpanjangan langganan: '.$e->getMessage());
                            }
                        }
                    }
                }
            } elseif ($subscription && $subscription->status !== SubscriptionStatus::Active && ! $isOutletAddition) {
                // Cancel any previous active subscriptions before activating the new one
                $business->subscriptions()
                    ->where('id', '!=', $subscription->id)
                    ->where('status', SubscriptionStatus::Active)
                    ->update([
                        'status' => SubscriptionStatus::Canceled,
                        'canceled_at' => $now,
                    ]);

                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                ]);

                // Send notification to the first user (business owner)
                $owner = $business->users()->first();
                if ($owner) {
                    try {
                        $expiredAt = $subscription->expired_at
                            ? $subscription->expired_at->translatedFormat('d F Y')
                            : 'Lifetime';

                        $owner->notify(new SubscriptionActivatedNotification(
                            $business,
                            $subscription->plan,
                            $expiredAt
                        ));
                    } catch (\Exception $e) {
                        Log::error('Gagal mengirim notifikasi aktivasi langganan: '.$e->getMessage());
                    }
                }
            }

            if ($isOutletAddition) {
                $outletAdditionItem = $invoice->items()->where('item_type', 'outlet_addition')->first();
                if ($outletAdditionItem && isset($outletAdditionItem->metadata['outlet_id'])) {
                    $outlet = \App\Models\Outlet::find($outletAdditionItem->metadata['outlet_id']);
                    if ($outlet && ! $outlet->is_active) {
                        $owner = $business->users()->first();
                        app(\App\Services\App\Outlet\ManageOutletStatusService::class)->toggleStatus($outlet, true, $owner);

                        if ($owner) {
                            try {
                                $owner->notify(new \App\Notifications\OutletActivatedNotification($outlet));
                            } catch (\Exception $e) {
                                Log::error('Gagal mengirim notifikasi aktivasi outlet: '.$e->getMessage());
                            }
                        }
                    }
                }
            }

            return $invoice;
        });

        return $invoice;
    }
}
