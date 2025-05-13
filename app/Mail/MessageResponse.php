<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $response = null;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $response)
    {
        $this->subject("Re: $subject");
        $this->response = $response;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->response,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
