<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Master\Outlet;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'outlet_id',
        'shift_id',
        'customer_id',
        'channel',
        'receipt_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_charge_amount',
        'total',
        'payment_status',
        'status',
        'is_offline',
        'offline_id',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'              => 'decimal:4',
            'discount_amount'       => 'decimal:4',
            'tax_amount'            => 'decimal:4',
            'service_charge_amount' => 'decimal:4',
            'total'                 => 'decimal:4',
            'is_offline'            => 'boolean',
            'due_date'              => 'date',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }
}
