<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperPromoInventoryItem
 */
class PromoInventoryItem extends Pivot
{
    use HasUuids;

    protected $table = 'promo_inventory_items';

    protected $fillable = [
        'promo_id',
        'inventory_item_id',
    ];
}
