<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasUuids, HasBusiness;

    protected $fillable = [
        'business_id',
        'item_type',
        'product_id',
        'raw_material_id',
        'sku',
        'barcode',
        'track_inventory',
        'current_stock',
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
        'current_stock' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variantGroupOptions()
    {
        return $this->belongsToMany(VariantGroupOption::class, 'inventory_item_variant_group_option');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
