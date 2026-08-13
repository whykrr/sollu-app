<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromoOutlet extends Pivot
{
    use HasUuids;

    protected $table = 'promo_outlets';

    protected $fillable = [
        'promo_id',
        'outlet_id',
    ];
}
