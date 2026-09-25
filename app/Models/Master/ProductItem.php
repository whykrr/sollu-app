<?php

namespace App\Models\Master;

use App\Models\Inventory\InventoryItem;
use App\Models\Uom;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
