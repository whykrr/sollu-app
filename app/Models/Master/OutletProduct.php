<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OutletProduct extends Pivot
{
    protected $table = 'outlet_product';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_available' => 'boolean',
    ];
}
