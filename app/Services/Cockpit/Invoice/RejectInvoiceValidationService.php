<?php

namespace App\Services\Cockpit\Invoice;

use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionPayment\Status as PaymentStatus;
use App\Models\Invoice;
use App\Notifications\InvoicePaymentRejectedNotification;
use Illuminate\Support\Facades\DB;

class RejectInvoiceValidationService
{
    public function execute(Invoice $invoice, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $validation = $invoice->paymentManualValidation;
            if ($validation) {
                $validation->update([
                    'validation_status' => PaymentManualValidationStatus::Rejected,
                    'rejection_reason' => $reason,
                    'reviewed_by' => auth('cockpit')->id(),
                    'reviewed_at' => now(),
                ]);
            }

            $invoice->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Failed->value,
                ]);

            // Notify business owner
            $business = $invoice->business;
            if ($business && $business->users()->exists()) {
                $owner = $business->users()->first();
                $owner?->notify(new InvoicePaymentRejectedNotification($invoice, $reason));
            }

            return $invoice;
        });
    }
}
