<?php

namespace App\Models\Sales;

use App\Enums\ShiftStatus;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin IdeHelperShift
 */
class Shift extends Model
{
    use HasBusiness, HasFactory, HasUuids, SortableModel;

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

    protected array $sortable = [
        'shift_number',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'total_sales',
        'status',
        'created_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'float',
            'closing_cash' => 'float',
            'expected_cash' => 'float',
            'total_sales' => 'float',
            'status' => ShiftStatus::class,
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

    public function scopeCurrentBusiness(Builder $query, ?string $businessId = null): Builder
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        return $query->whereHas('outlet', function (Builder $q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }

    public function scopeForOutlet(Builder $query, string|array $outletIds): Builder
    {
        $ids = array_filter((array) $outletIds);
        if (empty($ids)) {
            return $query;
        }

        return $query->whereIn($this->qualifyColumn('outlet_id'), $ids);
    }

    public function scopeFilters(Builder $query, array $filters): void
    {
        $user = Auth::user();

        if ($user && $user->business_id) {
            $query->currentBusiness($user->business_id);
        }

        $query->when($filters['outlet_id'] ?? null, function (Builder $query, $outletId) {
            $query->where('outlet_id', $outletId);
        })->when($filters['search'] ?? null, function (Builder $query, $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('shift_number', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['status'] ?? null, function (Builder $query, $status) {
            if ($status !== 'all') {
                $query->where('status', $status instanceof ShiftStatus ? $status->value : $status);
            }
        })->when($filters['start_date'] ?? null, function (Builder $query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        })->when($filters['end_date'] ?? null, function (Builder $query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });
    }
}
