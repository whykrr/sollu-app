<?php

namespace App\Models\Sales;

use App\Models\Master\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'outlet_id',
        'user_id',
        'shift_number',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'total_sales',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash'  => 'decimal:4',
            'closing_cash'  => 'decimal:4',
            'expected_cash' => 'decimal:4',
            'total_sales'   => 'decimal:4',
            'closed_at'     => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashLogs(): HasMany
    {
        return $this->hasMany(ShiftCashLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
