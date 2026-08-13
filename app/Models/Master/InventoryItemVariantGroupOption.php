<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperInventoryItemVariantGroupOption
 */
class InventoryItemVariantGroupOption extends Pivot
{
    protected $table = 'inventory_item_variant_group_option';

    public $incrementing = false;

    public $timestamps = false;
}
