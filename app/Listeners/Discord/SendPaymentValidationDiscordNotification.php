<?php

namespace App\Listeners\Discord;

use App\Events\Invoice\PaymentProofUploaded;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendPaymentValidationDiscordNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected DiscordWebhookService $discordService
    ) {}

    public function handle(PaymentProofUploaded $event): void
    {
        $invoice = $event->invoice->loadMissing(['business', 'items', 'paymentManualValidation']);
        $validation = $event->validation ?? $invoice->paymentManualValidation;
        $business = $invoice->business;

        $phone = $business?->phone ?? '-';
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $waPhone = '62'.substr($cleanPhone, 1);
        } else {
            $waPhone = $cleanPhone;
        }

        $phoneDisplay = $phone !== '-'
            ? "[{$phone}](https://wa.me/{$waPhone})"
            : '-';

        $ownerInfo = $business
            ? "{$business->owner_name} • {$phoneDisplay} • {$business->email}"
            : '-';

        $itemsSummary = $invoice->items->pluck('description')->filter()->implode(', ');
        if (empty($itemsSummary)) {
            $itemsSummary = 'Subscription Billing';
        }

        $proofPath = $validation?->payment_proof_url;
        $attachment = null;
        $attachmentFilename = 'payment_proof.jpg';

        $disk = null;
        if ($proofPath) {
            if (Storage::exists($proofPath)) {
                $disk = Storage::disk();
            } elseif (Storage::disk('public')->exists($proofPath)) {
                $disk = Storage::disk('public');
            }
        }

        if ($disk && $proofPath) {
            $ext = pathinfo($proofPath, PATHINFO_EXTENSION) ?: 'jpg';
            $attachmentFilename = "payment_proof_{$invoice->invoice_number}.{$ext}";
            $attachment = [
                'contents' => $disk->get($proofPath),
                'filename' => $attachmentFilename,
            ];
        }

        $cockpitInvoiceUrl = url(route('cockpit.invoices.index', ['open_invoice' => $invoice->invoice_number], false));

        $actionLinks = "👉 **[Validasi Pembayaran di Cockpit]({$cockpitInvoiceUrl})**";
        if ($validation?->payment_proof_full_url && filter_var($validation->payment_proof_full_url, FILTER_VALIDATE_URL)) {
            $actionLinks .= "\n🖼️ **[Buka Bukti Gambar Asli]({$validation->payment_proof_full_url})**";
        }

        $embed = [
            'title' => '💳 Permintaan Validasi Pembayaran Manual',
            'url' => $cockpitInvoiceUrl,
            'description' => "Pengguna telah mengunggah bukti transfer untuk invoice langganan dan menunggu verifikasi admin.\n\n👉 **[Validasi Pembayaran di Cockpit]({$cockpitInvoiceUrl})**",
            'color' => hexdec('F59E0B'), // Amber / Orange
            'fields' => [
                [
                    'name' => 'No. Invoice',
                    'value' => "**#{$invoice->invoice_number}**",
                    'inline' => true,
                ],
                [
                    'name' => 'Total Tagihan',
                    'value' => '**Rp '.number_format($invoice->total_amount, 0, ',', '.').'**',
                    'inline' => true,
                ],
                [
                    'name' => 'Merchant / Bisnis',
                    'value' => $business?->name ?? '-',
                    'inline' => true,
                ],
                [
                    'name' => 'Paket / Item',
                    'value' => $itemsSummary,
                    'inline' => true,
                ],
                [
                    'name' => 'Status',
                    'value' => '⏳ **Pending Review**',
                    'inline' => true,
                ],
                [
                    'name' => 'Kontak Pemilik',
                    'value' => $ownerInfo,
                    'inline' => false,
                ],
                [
                    'name' => '⚡ Aksi Validasi',
                    'value' => $actionLinks,
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'Sollu Billing System',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($attachment) {
            $embed['image'] = [
                'url' => "attachment://{$attachmentFilename}",
            ];
        } elseif ($validation?->payment_proof_full_url) {
            $embed['image'] = [
                'url' => $validation->payment_proof_full_url,
            ];
        }

        $buttons = [
            [
                'type' => 2, // Button
                'style' => 5, // Link
                'label' => '🔍 Validasi Pembayaran di Cockpit',
                'url' => $cockpitInvoiceUrl,
            ],
        ];

        if ($validation?->payment_proof_full_url && filter_var($validation->payment_proof_full_url, FILTER_VALIDATE_URL)) {
            $buttons[] = [
                'type' => 2,
                'style' => 5,
                'label' => '🔗 Buka Bukti Gambar',
                'url' => $validation->payment_proof_full_url,
            ];
        }

        $components = [
            [
                'type' => 1, // Action Row
                'components' => $buttons,
            ],
        ];

        $this->discordService->send('payment_validation', $embed, $components, $attachment);
    }

    public function failed(PaymentProofUploaded $event, Throwable $exception): void
    {
        // Handled silently with logger inside DiscordWebhookService
    }
}
