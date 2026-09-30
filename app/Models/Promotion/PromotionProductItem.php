<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProductItem extends Pivot
{
    use HasUuids;

    protected $table = 'promotion_product_items';

    public $incrementing = false;

    public $timestamps = false;
}
