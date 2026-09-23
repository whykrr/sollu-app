<?php

namespace App\Services\App\Invoice;

use App\Enums\PaymentManualValidationStatus;
use App\Events\Invoice\PaymentProofUploaded;
use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use App\Services\Core\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadPaymentProofService
{
    public function __construct(
        protected ?ImageOptimizerService $imageOptimizerService = null
    ) {
        $this->imageOptimizerService = $imageOptimizerService ?? app(ImageOptimizerService::class);
    }

    /**
     * Store payment proof, update/create manual validation record, and ensure a pending payment exists.
     *
     * @param  UploadedFile  $file
     */
    public function execute(Invoice $invoice, UploadFile|UploadedFile $file): PaymentManualValidation
    {
        return DB::transaction(function () use ($invoice, $file): PaymentManualValidation {
            $existingValidation = PaymentManualValidation::where('invoice_id', $invoice->id)->first();
            $oldProofPath = $existingValidation?->payment_proof_url;

            $path = $this->imageOptimizerService->optimizeAndStore(
                $file,
                'invoices/payment_proof',
                'payment_proof'
            );

            if ($oldProofPath && $oldProofPath !== $path) {
                $this->imageOptimizerService->delete($oldProofPath);
            }

            $validation = PaymentManualValidation::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'payment_proof_url' => $path,
                    'validation_status' => PaymentManualValidationStatus::Pending,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]
            );

            $payment = $invoice->payments()->where('payment_method', 'manual')->latest()->first();
            if (! $payment || $payment->status === 'failed') {
                $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'pending',
                    'payment_reference' => "{$invoice->invoice_number}-MANUAL-".Str::upper(Str::random(4)),
                ]);
            }

            PaymentProofUploaded::dispatch($invoice, $validation);

            return $validation;
        });
    }
}
