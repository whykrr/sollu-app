<?php

namespace App\Models\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read InventoryItem $inventoryItem
 * @property-read User|null $creator
 * @mixin \Eloquent
 */
class InventoryMovement extends Model
{
    use HasFactory;
    use HasUuids;
    use HasBusiness;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'inventory_item_id',
        'movement_type',
        'qty_change',
        'stock_before',
        'stock_after',
        'purchase_price',
        'description',
        'reference_id',
        'reference_type',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_type'  => InventoryMovementType::class,
            'qty_change'     => 'decimal:4',
            'stock_before'   => 'decimal:4',
            'stock_after'    => 'decimal:4',
            'purchase_price' => 'decimal:2',
            'created_at'     => 'datetime',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['movement_type'] ?? false,
            fn (Builder $q, $value) => $q->where('movement_type', $value)
        )->when(
            $filters['inventory_item_id'] ?? false,
            fn (Builder $q, $value) => $q->where('inventory_item_id', $value)
        )->when(
            $filters['date_from'] ?? false,
            fn (Builder $q, $value) => $q->where('created_at', '>=', $value)
        )->when(
            $filters['date_to'] ?? false,
            fn (Builder $q, $value) => $q->where('created_at', '<=', $value)
        );
    }

    // ── Polymorphic reference accessor ───────────────────────────

    /**
     * Get the referenced model (PO, Transfer, Opname, etc.).
     */
    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
