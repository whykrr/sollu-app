<?php

namespace App\Models\Master;

use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductBundleItem extends Model
{
    use HasUuids, SortableModel;

    protected $fillable = [
        'bundle_product_id',
        'component_product_id',
        'component_inventory_item_id',
        'qty',
        'sort_order',
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
}
