<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
        'product_item_id',
        'name',
        'uom_id',
        'minimum_stock',
        'is_active',
    ];

    protected $appends = [
        'minimum_stock_formatted',
        'sku',
        'barcode',
        'product_id',
        'item_type',
        'track_inventory',
    ];

    protected $with = ['productItem'];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'float',
            'is_active' => 'boolean',
        ];
    }

    // ── Accessors (Bridge to ProductItem) ────────────────────────

    protected function name(): Attribute
    {
        return Attribute::make(get: fn (?string $value) => $value ?? $this->productItem?->name);
    }

    protected function sku(): Attribute
    {
        return Attribute::make(get: fn () => $this->productItem?->sku);
    }

    protected function barcode(): Attribute
    {
        return Attribute::make(get: fn () => $this->productItem?->barcode);
    }

    protected function productId(): Attribute
    {
        return Attribute::make(get: fn () => $this->productItem?->product_id);
    }

    protected function itemType(): Attribute
    {
        return Attribute::make(get: fn () => $this->productItem?->item_type);
    }

    protected function trackInventory(): Attribute
    {
        return Attribute::make(get: fn () => $this->productItem?->track_inventory ?? false);
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    // Retaining product relationship via productItem for convenience if needed by other components
    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            ProductItem::class,
            'id', // Foreign key on ProductItem table...
            'id', // Foreign key on Product table...
            'product_item_id', // Local key on InventoryItem table...
            'product_id' // Local key on ProductItem table...
        );
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

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_inventory_items');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->whereHas('productItem', function ($q) use ($value) {
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('sku', "%{$value}%")
                    ->orWhereLike('barcode', "%{$value}%")
                    ->orWhereLike('variant_combination', "%{$value}%");
            })
        )->when(
            $filters['item_type'] ?? false,
            fn (Builder $q, $value) => $q->whereHas('productItem', function ($q) use ($value) {
                $q->where('item_type', $value);
            })
        )->when(
            isset($filters['track_inventory']),
            fn (Builder $q) => $q->whereHas('productItem', function ($q) use ($filters) {
                $q->where('track_inventory', $filters['track_inventory']);
            })
        );
    }

    protected function minimumStockFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->minimum_stock),
        );
    }
}
