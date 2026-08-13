<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Models\Traits\HasQuantityFormatter;
use App\Models\Uom;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Business $business
 * @property-read Uom|null $uom
 * @property-read Collection|InventoryBalance[] $balances
 * @property-read Collection|InventoryMovement[] $movements
 * @property-read Collection|InventoryCostLayer[] $costLayers
 *
 * @mixin \Eloquent
 * @mixin IdeHelperInventoryItem
 */
class InventoryItem extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'item_type',
        'product_id',
        'sku',
        'barcode',
        'uom_id',
        'track_inventory',
        'minimum_stock',
        'is_active',
    ];

    protected $appends = [
        'minimum_stock_formatted',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'minimum_stock' => 'float',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function costLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }

    public function suppliers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_inventory_items');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->where(function ($q) use ($value) {
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('sku', "%{$value}%")
                    ->orWhereLike('barcode', "%{$value}%");
            })
        )->when(
            $filters['item_type'] ?? false,
            fn (Builder $q, $value) => $q->where('item_type', $value)
        )->when(
            isset($filters['track_inventory']),
            fn (Builder $q) => $q->where('track_inventory', $filters['track_inventory'])
        );
    }

    protected function minimumStockFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->minimum_stock),
        );
    }
}
