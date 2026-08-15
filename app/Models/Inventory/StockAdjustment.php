<?php

namespace App\Models\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property string $business_id
 * @property string $outlet_id
 * @property string $adjustment_number
 * @property \App\Enums\AdjustmentStatus $status
 * @property \App\Enums\AdjustmentReason $reason
 * @property string|null $notes
 * @property string $created_by
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Outlet $outlet
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Inventory\StockAdjustmentItem[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Inventory\InventoryMovement[] $inventoryMovements
 * @mixin IdeHelperStockAdjustment
 */
class StockAdjustment extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'adjustment_number',
        'status',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdjustmentStatus::class,
            'reason' => AdjustmentReason::class,
            'approved_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            function (Builder $q, $value) {
                $q->where(function ($sub) use ($value) {
                    $sub->where('adjustment_number', 'ilike', '%'.$value.'%')
                        ->orWhereHas('items.inventoryItem', function ($itemQ) use ($value) {
                            $itemQ->where('name', 'ilike', '%'.$value.'%');
                        });
                });
            }
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['reason'] ?? false,
            fn (Builder $q, $value) => $q->where('reason', $value)
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
