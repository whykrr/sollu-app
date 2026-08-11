<?php

namespace App\Models\Sales;

use App\Models\Master\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_id',
        'payment_method_id',
        'amount',
        'change_amount',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:4',
            'change_amount' => 'decimal:4',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
