<?php

namespace App\Models\Master;

use App\Models\Traits\HasQuantityFormatter;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasBusiness;
    use HasQuantityFormatter;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'item_type',
        'product_id',
        'raw_material_id',
        'sku',
        'barcode',
        'track_inventory',
        'is_active',
        'min_stock',
        'uom_id',
    ];

    protected $appends = [
        'min_stock_formatted',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'min_stock' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function minStock(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['minimum_stock'] ?? 0,
            set: fn ($value) => ['minimum_stock' => $value]
        );
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uom::class);
    }

    public function variantGroupOptions(): BelongsToMany
    {
        return $this->belongsToMany(VariantGroupOption::class, 'inventory_item_variant_group_option');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    protected function minStockFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->min_stock),
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
