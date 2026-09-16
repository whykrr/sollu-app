<?php

namespace App\Mail;

use App\Models\Outlet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutletActivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Outlet $outlet) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Penambahan Outlet Anda Telah Aktif',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.outlets.activated',
            with: [
                'outlet' => $this->outlet,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
