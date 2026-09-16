<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public SubscriptionPlan $plan,
        public string $expiredAt,
        public int $daysRemaining
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysRemaining > 0
            ? "Pengingat Perpanjangan Langganan ({$this->daysRemaining} hari lagi)"
            : 'Langganan Anda Telah Berakhir';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscriptions.renewal',
            with: [
                'renewUrl' => route('settings.billing.checkout', ['plan_id' => $this->plan->id]).'?is_renewal=1',
            ]
        );
    }
}
