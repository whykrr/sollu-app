<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'recipe_version_id',
        'inventory_item_id',
        'qty',
        'uom',
    ];

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
