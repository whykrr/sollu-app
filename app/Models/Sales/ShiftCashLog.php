<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperShiftCashLog
 */
class ShiftCashLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shift_id',
        'type',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
