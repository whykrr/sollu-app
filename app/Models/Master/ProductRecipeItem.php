<?php

namespace App\Models\Master;

use App\Models\Traits\HasQuantityFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperProductRecipeItem
 */
class ProductRecipeItem extends Model
{
    use HasQuantityFormatter;
    use HasUuids;

    protected $fillable = [
        'recipe_version_id',
        'product_item_id',
        'qty',
        'uom',
    ];

    protected $appends = [
        'qty_formatted',
    ];

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    protected function qtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty),
        );
    }
}
