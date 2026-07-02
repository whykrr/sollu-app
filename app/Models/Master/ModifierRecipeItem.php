<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModifierRecipeItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'modifier_option_id',
        'inventory_item_id',
        'qty',
        'uom',
    ];

    public function modifierOption()
    {
        return $this->belongsTo(ModifierOption::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
