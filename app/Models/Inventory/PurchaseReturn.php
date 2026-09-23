<?php

namespace App\Models\Inventory;

use App\Enums\PurchaseReturnStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
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
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Supplier|null $supplier
 * @property-read User|null $creator
 * @property-read Collection|PurchaseReturnItem[] $items
 * @mixin \Eloquent
 * @mixin IdeHelperPurchaseReturn
 */
class PurchaseReturn extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'purchase_order_id',
        'supplier_id',
        'return_number',
        'return_date',
        'reason',
        'total_return_amount',
        'status',
        'created_by',
    ];

    protected array $sortable = [
        'return_number',
        'return_date',
        'total_return_amount',
        'status',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseReturnStatus::class,
            'return_date' => 'date',
            'total_return_amount' => 'float',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->whereLike('return_number', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['supplier_id'] ?? false,
            fn (Builder $q, $value) => $q->where('supplier_id', $value)
        )->when(
            $filters['purchase_order_id'] ?? false,
            fn (Builder $q, $value) => $q->where('purchase_order_id', $value)
        )->when(
            $filters['outlet_id'] ?? \App\Helpers\SelectedOutlet::make()->currentId(),
            fn (Builder $q, $value) => $q->where('outlet_id', $value)
        )->when(
            $filters['start_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('return_date', '>=', $value)
        )->when(
            $filters['end_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('return_date', '<=', $value)
        );
    }
}
