<?php

namespace App\Models\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Traits\HasQuantityFormatter;
use App\Models\User;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read InventoryItem $inventoryItem
 * @property-read User|null $creator
 *
 * @mixin \Eloquent
 */
class InventoryMovement extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'inventory_item_id',
        'movement_type',
        'qty_change',
        'stock_before',
        'stock_after',
        'cost',
        'description',
        'reference_id',
        'reference_type',
        'created_by',
        'created_at',
    ];

    protected $appends = [
        'qty_change_formatted',
        'stock_before_formatted',
        'stock_after_formatted',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
            'qty_change' => 'float',
            'stock_before' => 'float',
            'stock_after' => 'float',
            'purchase_price' => 'float',
            'created_at' => 'datetime',
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

    protected function qtyChangeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty_change),
        );
    }

    protected function stockBeforeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->stock_before),
        );
    }

    protected function stockAfterFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->stock_after),
        );
    }
}
