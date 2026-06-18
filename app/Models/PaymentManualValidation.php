<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Support\Facades\Storage;

class PaymentManualValidation extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $appends = ['payment_proof_full_url'];

    public function getPaymentProofFullUrlAttribute()
    {
        return $this->payment_proof_url
            ? Storage::url($this->payment_proof_url)
            : null;
    }
}
