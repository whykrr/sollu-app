<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperPromoProduct
 */
class PromoProduct extends Pivot
{
    use HasUuids;

    protected $table = 'promo_products';

    protected $fillable = [
        'promo_id',
        'product_id',
    ];
}
