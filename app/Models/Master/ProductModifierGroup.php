<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductModifierGroup
 */
class ProductModifierGroup extends Pivot
{
    protected $table = 'product_modifier_groups';

    public $incrementing = false;

    public $timestamps = false;
}
