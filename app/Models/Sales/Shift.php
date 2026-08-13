<?php

namespace App\Models\Sales;

use App\Models\Master\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperShift
 */
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
            'opening_cash' => 'float',
            'closing_cash' => 'float',
            'expected_cash' => 'float',
            'total_sales' => 'float',
            'closed_at' => 'datetime',
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

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['start_date'] ?? null, function ($query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        })->when($filters['end_date'] ?? null, function ($query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });
    }
}
