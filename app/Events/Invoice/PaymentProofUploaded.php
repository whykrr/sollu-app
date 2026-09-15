<?php

namespace App\Events\Invoice;

use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProofUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly PaymentManualValidation $validation
    ) {}
}
