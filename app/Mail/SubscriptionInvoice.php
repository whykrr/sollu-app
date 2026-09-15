<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?Payment $payment = null
    ) {
        $this->invoice->loadMissing(['business', 'items', 'payments']);

        if (! $this->payment) {
            $this->payment = $this->invoice->payments()->latest()->first();
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Pembayaran Langganan #'.$this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoices.subscription_invoice',
            with: [
                'invoice' => $this->invoice,
                'business' => $this->invoice->business,
                'payment' => $this->payment,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $this->invoice->loadMissing(['business', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
            'payment' => $this->payment,
        ])->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "Invoice-{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
