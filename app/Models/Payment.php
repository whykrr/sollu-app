<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'payment_method',
        'payment_reference',
        'amount',
        'status',
        'json_request',
        'json_respond',
        'paid_at',
    ];

    protected $appends = ['order_id'];

    protected function casts(): array
    {
        return [
            'json_request' => 'json',
            'json_respond' => 'json',
            'paid_at' => 'datetime',
        ];
    }

    public function getOrderIdAttribute()
    {
        return $this->payment_reference;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
