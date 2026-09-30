<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProduct extends Pivot
{
    use HasUuids;

    protected $table = 'promotion_products';

    public $incrementing = false;

    public $timestamps = false;
}
