<?php

namespace App\Models\Master;

use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperVariantGroupOption
 */
class VariantGroupOption extends Model
{
    use HasUuids, SortableModel;

    protected $fillable = [
        'variant_group_id',
        'name',
        'sort_order',
    ];

    public function variantGroup(): BelongsTo
    {
        return $this->belongsTo(VariantGroup::class);
    }

    public function productItems(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductItem::class,
            'product_item_variant_group_options',
            'variant_group_option_id',
            'product_item_id'
        );
    }
}
