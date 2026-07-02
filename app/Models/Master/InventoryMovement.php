<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasUuids, HasBusiness;

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
    ];

    protected $casts = [
        'qty_change' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
