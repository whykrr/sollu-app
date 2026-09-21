<?php

namespace App\Models\Inventory;

use App\Enums\GoodsReceiptStatus;
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
 * @property-read User|null $receiver
 * @property-read Collection|GoodsReceiptItem[] $items
 *
 * @mixin \Eloquent
 * @mixin IdeHelperGoodsReceipt
 */
class GoodsReceipt extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'purchase_order_id',
        'receipt_number',
        'delivery_order_number',
        'received_at',
        'status',
        'notes',
        'received_by',
    ];

    protected array $sortable = [
        'receipt_number',
        'delivery_order_number',
        'received_at',
        'status',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptStatus::class,
            'received_at' => 'datetime',
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->where(function ($query) use ($value) {
                $query->whereLike('receipt_number', "%{$value}%")
                    ->orWhereLike('delivery_order_number', "%{$value}%");
            })
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['purchase_order_id'] ?? false,
            fn (Builder $q, $value) => $q->where('purchase_order_id', $value)
        )->when(
            $filters['outlet_id'] ?? \App\Helpers\SelectedOutlet::make()->currentId(),
            fn (Builder $q, $value) => $q->where('outlet_id', $value)
        )->when(
            $filters['start_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('received_at', '>=', $value)
        )->when(
            $filters['end_date'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('received_at', '<=', $value)
        );
    }
}
