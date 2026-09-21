<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Mail\SubscriptionInvoice;
use App\Models\Invoice;
use App\Models\Payment;

class SubscriptionInvoicePaidNotification extends BaseNotification
{
    public function __construct(
        public Invoice $invoice,
        public ?Payment $payment = null
    ) {
        $this->invoice->loadMissing(['business', 'items', 'payments']);

        if (! $this->payment) {
            $this->payment = $this->invoice->payments()->latest()->first();
        }

        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::BUSINESS;
        $this->businessId = $this->invoice->business_id;
        $this->title = 'Pembayaran Invoice Terverifikasi';
        $this->message = 'Pembayaran untuk invoice #'.$this->invoice->invoice_number.' sebesar Rp '.number_format($this->invoice->total_amount, 0, ',', '.').' telah diverifikasi.';
        $this->actionUrl = route('settings.billing.invoices.show', $this->invoice->invoice_number);
        $this->actionText = 'Lihat Invoice';
        $this->meta = [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
        ];
    }

    public function toMail(object $notifiable)
    {
        $recipientEmail = $notifiable->email ?? $notifiable->routeNotificationFor('mail');

        return (new SubscriptionInvoice($this->invoice, $this->payment))
            ->to($recipientEmail);
    }
}
