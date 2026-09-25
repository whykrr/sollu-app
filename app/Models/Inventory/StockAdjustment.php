<?php

namespace App\Models\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Helpers\SelectedOutlet;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $business_id
 * @property string $outlet_id
 * @property string $adjustment_number
 * @property AdjustmentStatus $status
 * @property AdjustmentReason $reason
 * @property string|null $notes
 * @property string $created_by
 * @property string|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read User $creator
 * @property-read User|null $approver
 * @property-read Collection|StockAdjustmentItem[] $items
 * @property-read Collection|InventoryMovement[] $inventoryMovements
 *
 * @mixin IdeHelperStockAdjustment
 */
class StockAdjustment extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

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

    protected array $sortable = [
        'adjustment_number',
        'created_at',
        'status',
        'reason',
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
            $filters['outlet_id'] ?? SelectedOutlet::make()->currentId(),
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
