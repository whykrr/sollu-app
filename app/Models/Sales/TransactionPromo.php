<?php

namespace App\Models\Sales;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPromo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_id',
        'promo_id',
        'promo_name',
        'promo_code',
        'discount_type',
        'discount_value',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'float',
            'discount_amount' => 'float',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }
}
