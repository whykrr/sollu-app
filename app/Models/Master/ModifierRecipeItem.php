<?php

namespace App\Models\Master;

use App\Models\Traits\HasQuantityFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperModifierRecipeItem
 */
class ModifierRecipeItem extends Model
{
    use HasQuantityFormatter;
    use HasUuids;

    protected $fillable = [
        'modifier_option_id',
        'product_item_id',
        'qty',
        'uom',
    ];

    protected $appends = [
        'qty_formatted',
    ];

    public function modifierOption()
    {
        return $this->belongsTo(ModifierOption::class);
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
