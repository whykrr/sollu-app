<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Master\Outlet;
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
            'subtotal' => 'float',
            'discount_amount' => 'float',
            'tax_amount' => 'float',
            'service_charge_amount' => 'float',
            'total' => 'float',
            'is_offline' => 'boolean',
            'due_date' => 'date',
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

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('receipt_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['channel'] ?? null, function ($query, $channel) {
            $query->where('channel', $channel);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['payment_status'] ?? null, function ($query, $paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        })->when($filters['start_date'] ?? null, function ($query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        })->when($filters['end_date'] ?? null, function ($query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });
    }
}
