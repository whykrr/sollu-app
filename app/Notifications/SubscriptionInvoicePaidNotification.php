<?php

namespace App\Notifications;

use App\Mail\SubscriptionInvoice;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionInvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public ?Payment $payment = null
    ) {
        $this->invoice->loadMissing(['business', 'items', 'payments']);

        if (! $this->payment) {
            $this->payment = $this->invoice->payments()->latest()->first();
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        $recipientEmail = $notifiable->email ?? $notifiable->routeNotificationFor('mail');

        return (new SubscriptionInvoice($this->invoice, $this->payment))
            ->to($recipientEmail);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_invoice_paid',
            'title' => 'Pembayaran Invoice Terverifikasi',
            'message' => 'Pembayaran untuk invoice #'.$this->invoice->invoice_number.' sebesar Rp '.number_format($this->invoice->total_amount, 0, ',', '.').' telah diverifikasi.',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
        ];
    }
}
