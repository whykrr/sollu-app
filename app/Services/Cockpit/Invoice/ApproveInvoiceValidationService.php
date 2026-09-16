<?php

namespace App\Services\Cockpit\Invoice;

use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionPayment\Status as PaymentStatus;
use App\Models\Invoice;
use App\Services\App\Invoice\CompleteInvoiceService;
use Illuminate\Support\Facades\DB;

class ApproveInvoiceValidationService
{
    public function __construct(
        protected CompleteInvoiceService $completeInvoiceService
    ) {}

    public function execute(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $this->completeInvoiceService->execute($invoice);

            $invoice->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Settlement->value,
                    'paid_at' => now(),
                ]);

            $validation = $invoice->paymentManualValidation;
            if ($validation) {
                $validation->update([
                    'validation_status' => PaymentManualValidationStatus::Approved,
                    'reviewed_by' => auth('cockpit')->id(),
                    'reviewed_at' => now(),
                ]);
            }

            return $invoice;
        });
    }
}
