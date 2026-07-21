<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Traits\HasQuantityFormatter;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasQuantityFormatter;

    use HasUuids;
    use HasBusiness;

    protected $fillable = [
        'business_id',
        'item_type',
        'product_id',
        'raw_material_id',
        'sku',
        'barcode',
        'track_inventory',
        'min_stock',
    ];

        protected $appends = [
        'min_stock_formatted',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'min_stock'       => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
}
