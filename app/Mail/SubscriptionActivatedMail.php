<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public SubscriptionPlan $plan,
        public string $expiredAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Langganan Anda Telah Aktif',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoices.subscription_activated',
        );
    }
}
