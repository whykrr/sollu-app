<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionCategory extends Pivot
{
    use HasUuids;

    protected $table = 'promotion_categories';

    public $incrementing = false;

    public $timestamps = false;
}
