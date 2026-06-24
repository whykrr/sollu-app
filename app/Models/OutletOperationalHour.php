<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletOperationalHour extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['outlet_id', 'day_of_week', 'open_time', 'close_time', 'is_closed'];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
