<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'outlet_id',
        'shift_id',
        'customer_id',
        'channel',
        'transaction_number',
        'subtotal',
        'discount_amount',
        'discount_type',
        'discount_value',
        'promo_name',
        'tax_amount',
        'shipping_fee',
        'service_charge_amount',
        'total',
        'payment_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'discount_amount' => 'float',
            'discount_value' => 'float',
            'tax_amount' => 'float',
            'shipping_fee' => 'float',
            'service_charge_amount' => 'float',
            'total' => 'float',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Outlet::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function modifiers(): HasManyThrough
    {
        return $this->hasManyThrough(TransactionItemModifier::class, TransactionItem::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TransactionInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function promos(): HasMany
    {
        return $this->hasMany(TransactionPromo::class);
    }

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', '%'.$search.'%')
                    ->orWhereHas('invoice', function ($query) use ($search) {
                        $query->where('invoice_number', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['channel'] ?? null, function ($query, $channel) {
            $query->where('channel', $channel);
        })->when($filters['status'] ?? null, function ($query, $status) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        })->when($filters['payment_status'] ?? null, function ($query, $paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        })->when($filters['start_date'] ?? null, function ($query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        })->when($filters['end_date'] ?? null, function ($query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });
    }
}
