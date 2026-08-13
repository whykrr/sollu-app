<?php

namespace App\Models\Master;

use App\Models\Traits\HasQuantityFormatter;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperInventoryMovement
 */
class InventoryMovement extends Model
{
    use HasBusiness, HasUuids;
    use HasQuantityFormatter;

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'inventory_item_id',
        'movement_type',
        'qty_change',
        'stock_before',
        'stock_after',
        'reference_id',
        'reference_type',
        'created_by',
        'purchase_price',
        'description',
    ];

    protected $appends = [
        'qty_change_formatted',
        'stock_before_formatted',
        'stock_after_formatted',
    ];

    protected $casts = [
        'qty_change' => 'float',
        'stock_before' => 'float',
        'stock_after' => 'float',
        'purchase_price' => 'float',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
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
