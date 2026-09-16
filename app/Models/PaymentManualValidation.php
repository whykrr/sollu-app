<?php

namespace App\Models;

use App\Enums\PaymentManualValidationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin IdeHelperPaymentManualValidation
 */
class PaymentManualValidation extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $appends = ['payment_proof_full_url'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'validation_status' => PaymentManualValidationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getPaymentProofFullUrlAttribute(): ?string
    {
        return $this->payment_proof_url
            ? Storage::url($this->payment_proof_url)
            : null;
    }
}
