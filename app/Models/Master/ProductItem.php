<?php

namespace App\Models\Master;

use App\Models\Inventory\InventoryItem;
use App\Models\Uom;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductItem extends Model
{
    use HasBusiness;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'item_type',
        'product_id',
        'uom_id',
        'name',
        'variant_combination',
        'sku',
        'barcode',
        'track_inventory',
        'is_show',
        'sellable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'is_show' => 'boolean',
            'sellable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductItem $item) {
            if (! $item->sellable) {
                $item->is_show = false;
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function variantGroupOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            VariantGroupOption::class,
            'product_item_variant_group_options',
            'product_item_id',
            'variant_group_option_id'
        );
    }

    public function recipeVersions(): HasMany
    {
        return $this->hasMany(RecipeVersion::class, 'product_item_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->where(function ($q) use ($value) {
                // Because we added a FULLTEXT index on name, we could use whereFullText,
                // but whereLike is safer as a fallback if DB doesn't support it or for partial matching.
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('sku', "%{$value}%")
                    ->orWhereLike('barcode', "%{$value}%")
                    ->orWhereLike('variant_combination', "%{$value}%");
            })
        )->when(
            $filters['item_type'] ?? false,
            fn (Builder $q, $value) => $q->where('item_type', $value)
        )->when(
            isset($filters['track_inventory']),
            fn (Builder $q) => $q->where('track_inventory', $filters['track_inventory'])
        )->when(
            isset($filters['is_show']),
            fn (Builder $q) => $q->where('is_show', $filters['is_show'])
        )->when(
            isset($filters['sellable']),
            fn (Builder $q) => $q->where('sellable', $filters['sellable'])
        );
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }

    public function scopeSellable(Builder $builder): Builder
    {
        return $builder->where('sellable', true);
    }

    public function scopeVisible(Builder $builder): Builder
    {
        return $builder->where('is_show', true);
    }

    public function scopeSingle(Builder $builder): Builder
    {
        return $builder->whereNull('variant_combination');
    }

    public function scopeVariant(Builder $builder): Builder
    {
        return $builder->whereNotNull('variant_combination');
    }

    public function scopeRawMaterials(Builder $builder): Builder
    {
        return $builder->where('item_type', 'raw_material');
    }

    public function scopeGoods(Builder $builder): Builder
    {
        return $builder->where('item_type', 'variant_sku');
    }

    public function isSingle(): bool
    {
        return is_null($this->variant_combination);
    }

    public function isVariant(): bool
    {
        return ! is_null($this->variant_combination);
    }

    public function isRawMaterial(): bool
    {
        return $this->item_type === 'raw_material';
    }
}
