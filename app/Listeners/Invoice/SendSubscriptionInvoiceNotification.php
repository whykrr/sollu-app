<?php

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoicePaid;
use App\Notifications\SubscriptionInvoicePaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendSubscriptionInvoiceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice->loadMissing(['business.users', 'items', 'payments']);
        $business = $invoice->business;

        if (! $business) {
            return;
        }

        $payment = $invoice->payments()->latest()->first();
        $owner = $business->users()->first();

        try {
            if ($owner) {
                $owner->notify(new SubscriptionInvoicePaidNotification($invoice, $payment));
            } elseif ($business->email) {
                Notification::route('mail', $business->email)
                    ->notify(new SubscriptionInvoicePaidNotification($invoice, $payment));
            }
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email invoice langganan #{$invoice->invoice_number}: ".$e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
            ]);
        }
    }
}
