<?php

namespace App\Models\Master;

use App\Models\Traits\HasQuantityFormatter;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperProductBundleItem
 */
class ProductBundleItem extends Model
{
    use HasQuantityFormatter;
    use HasUuids, SortableModel;

    protected $fillable = [
        'bundle_product_id',
        'component_product_id',
        'component_inventory_item_id',
        'qty',
        'sort_order',
    ];

    protected $appends = [
        'qty_formatted',
    ];

    public function bundleProduct()
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function componentInventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'component_inventory_item_id');
    }

    protected function qtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty),
        );
    }
}
