<?php

namespace App\Models\Inventory;

use App\Enums\StockOpnameStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read User|null $creator
 * @property-read User|null $approver
 * @property-read Collection|StockOpnameItem[] $items
 *
 * @mixin \Eloquent
 */
class StockOpname extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'opname_number',
        'status',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'status' => StockOpnameStatus::class,
    ];

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->whereLike('opname_number', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['outlet_id'] ?? false,
            fn (Builder $q, $value) => $q->where('outlet_id', $value)
        )->when(
            $filters['date_from'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('created_at', '>=', $value)
        )->when(
            $filters['date_to'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('created_at', '<=', $value)
        );
    }
}
