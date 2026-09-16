<?php

namespace App\Services\App\Invoice;

use App\Models\Invoice;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ChangePaymentMethodService
{
    /**
     * Change payment method for an unpaid invoice.
     *
     *
     * @throws InvalidArgumentException
     */
    public function execute(Invoice $invoice, string $paymentMethod): void
    {
        if ($paymentMethod === 'midtrans') {
            $isMidtransEnabled = SystemSetting::isMidtransEnabled();
            if (! $isMidtransEnabled) {
                throw new InvalidArgumentException('Metode pembayaran otomatis saat ini sedang dinonaktifkan.');
            }
        }

        DB::transaction(function () use ($invoice, $paymentMethod): void {
            // Delete any pending payments
            $invoice->payments()->where('status', 'pending')->delete();

            if ($paymentMethod === 'manual') {
                $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'pending',
                    'payment_reference' => "{$invoice->invoice_number}-MANUAL-".Str::upper(Str::random(4)),
                ]);
            }
        });
    }
}
