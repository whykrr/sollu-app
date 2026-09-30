<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Master\Customer;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $guarded = ['id'];

    /**
     * Whitelist column names for sorting.
     */
    protected array $sortable = [
        'transaction_number',
        'transaction_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_fee',
        'type',
        'total',
        'total_paid',
        'balance_due',
        'payment_status',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'shipping_fee' => 'decimal:4',
            'service_charge_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'total_paid' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'channel' => SalesChannelEnum::class,
            'type' => TransactionTypeEnum::class,
            'payment_status' => TransactionPaymentStatus::class,
            'status' => TransactionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Outlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * @return HasMany<TransactionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    /**
     * @return HasMany<TransactionPromo, $this>
     */
    public function promos(): HasMany
    {
        return $this->hasMany(TransactionPromo::class);
    }

    /**
     * @return HasMany<TransactionPromo, $this>
     */
    public function transactionPromos(): HasMany
    {
        return $this->hasMany(TransactionPromo::class)->whereNull('transaction_item_id');
    }

    /**
     * @return HasMany<TransactionPromo, $this>
     */
    public function itemPromos(): HasMany
    {
        return $this->hasMany(TransactionPromo::class)->whereNotNull('transaction_item_id');
    }

    /**
     * @return HasOne<TransactionInvoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(TransactionInvoice::class);
    }

    /**
     * Scope query to the current user's business through outlet.
     */
    public function scopeCurrentBusiness(Builder $query, ?string $businessId = null): Builder
    {
        $businessId = $businessId ?? auth()->user()?->business_id;

        return $query->whereHas('outlet', fn (Builder $q) => $q->where('business_id', $businessId));
    }

    /**
     * Scope query to specified outlet(s).
     *
     * @param  string|array<string>  $outletIds
     */
    public function scopeForOutlet(Builder $query, string|array $outletIds): Builder
    {
        $ids = array_values(array_filter((array) $outletIds));
        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($this->qualifyColumn('outlet_id'), $ids);
    }

    /**
     * Scope query for invoice transactions.
     */
    public function scopeInvoice(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('type'), TransactionTypeEnum::Invoice->value);
    }

    /**
     * Scope query for POS transactions.
     */
    public function scopePos(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('type'), TransactionTypeEnum::Pos->value);
    }

    /**
     * Scope query with sales transaction filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['type'] ?? null,
                fn (Builder $q, $type) => $q->where(
                    'type',
                    $type instanceof TransactionTypeEnum ? $type->value : $type
                )
            )
            ->when(
                $filters['channel'] ?? null,
                fn (Builder $q, $channel) => $q->where(
                    'channel',
                    $channel instanceof SalesChannelEnum ? $channel->value : $channel
                )
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $q, $status) => $q->where(
                    'status',
                    $status instanceof TransactionStatus ? $status->value : $status
                )
            )
            ->when(
                $filters['payment_status'] ?? null,
                fn (Builder $q, $paymentStatus) => $q->where(
                    'payment_status',
                    $paymentStatus instanceof TransactionPaymentStatus ? $paymentStatus->value : $paymentStatus
                )
            )
            ->when(
                $filters['search'] ?? null,
                function (Builder $q, $search) {
                    $search = trim((string) $search);
                    $q->where(function (Builder $sub) use ($search) {
                        $sub->whereLike('transaction_number', "%{$search}%")
                            ->orWhereHas('invoice', fn (Builder $inv) => $inv->whereLike('invoice_number', "%{$search}%"))
                            ->orWhereHas('customer', fn (Builder $cust) => $cust->whereLike('name', "%{$search}%"));
                    });
                }
            )
            ->when(
                $filters['start_date'] ?? null,
                fn (Builder $q, $startDate) => $q->whereDate('transaction_date', '>=', $startDate)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (Builder $q, $endDate) => $q->whereDate('transaction_date', '<=', $endDate)
            );
    }
}
