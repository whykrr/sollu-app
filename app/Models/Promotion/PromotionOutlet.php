<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionOutlet extends Pivot
{
    use HasUuids;

    protected $table = 'promotion_outlets';

    public $incrementing = false;

    public $timestamps = false;
}
