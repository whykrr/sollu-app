<?php

namespace App\Models;

use App\Enums\SubscriptionInvoice\Status;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperInvoice
 */
class Invoice extends Model
{
    use HasFactory, HasUuids, SortableModel;

    protected $fillable = [
        'business_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'due_date',
        'paid_at',
    ];

    /**
     * The attributes that are sortable.
     *
     * @var array<int, string>
     */
    protected array $sortable = [
        'invoice_number',
        'total_amount',
        'status',
        'created_at',
        'due_date',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'status' => Status::class,
            'paid_at' => 'datetime',
            'subtotal' => 'float',
            'tax_amount' => 'float',
            'total_amount' => 'float',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentManualValidation(): HasOne
    {
        return $this->hasOne(PaymentManualValidation::class);
    }

    /**
     * Scope query untuk filter pencarian dan status invoice.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilters(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where('invoice_number', 'ilike', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function (Builder $q, string $status) {
                $q->where('status', $status);
            });
    }
}
