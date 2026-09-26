<?php

namespace App\Models\Sales;

use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Master\Customer;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasBusiness, HasFactory, HasUuids, SortableModel;

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
        'total_paid',
        'balance_due',
        'transaction_date',
        'payment_status',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected array $sortable = [
        'transaction_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'status',
        'payment_status',
        'created_at',
        'updated_at',
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
            'total_paid' => 'float',
            'balance_due' => 'float',
            'transaction_date' => 'datetime',
            'status' => TransactionStatus::class,
            'payment_status' => TransactionPaymentStatus::class,
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

    public function modifiers(): HasManyThrough
    {
        return $this->hasManyThrough(TransactionItemModifier::class, TransactionItem::class);
    }

    public function invoice(): HasOne
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCurrentBusiness(Builder $query, ?string $businessId = null): Builder
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        return $query->whereHas('outlet', function (Builder $q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }

    public function scopeForOutlet(Builder $query, string|array $outletIds): Builder
    {
        $ids = array_filter((array) $outletIds);
        if (empty($ids)) {
            return $query;
        }

        return $query->whereIn($this->qualifyColumn('outlet_id'), $ids);
    }

    public function scopeFilters($query, array $filters)
    {
        $user = Auth::user();
        if ($user && $user->business_id) {
            $query->currentBusiness($user->business_id);
        }

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', '%'.$search.'%')
                    ->orWhereHas('invoice', function ($query) use ($search) {
                        $query->where('invoice_number', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['outlet_id'] ?? null, function ($query, $outletId) {
            $query->where('outlet_id', $outletId);
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
