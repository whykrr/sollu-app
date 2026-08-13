<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read Supplier $supplier
 * @property-read User|null $creator
 * @property-read User|null $approver
 * @property-read Collection|PurchaseOrderItem[] $items
 *
 * @mixin \Eloquent
 */
class PurchaseOrder extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'business_id',
        'outlet_id',
        'supplier_id',
        'po_number',
        'status',
        'order_date',
        'expected_date',
        'notes',
        'total_amount',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->whereLike('po_number', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['supplier_id'] ?? false,
            fn (Builder $q, $value) => $q->where('supplier_id', $value)
        )->when(
            $filters['outlet_id'] ?? false,
            fn (Builder $q, $value) => $q->where('outlet_id', $value)
        )->when(
            $filters['start_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('order_date', '>=', $value)
        )->when(
            $filters['end_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('order_date', '<=', $value)
        );
    }
}
