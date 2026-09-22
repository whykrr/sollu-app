<?php

namespace App\Models\Inventory;

use App\Enums\StockTransferStatus;
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

/**
 * @property string $id
 * @property string $business_id
 * @property string $from_outlet_id
 * @property string $to_outlet_id
 * @property string $transfer_number
 * @property \App\Enums\StockTransferStatus $status
 * @property string|null $notes
 * @property string|null $requested_by
 * @property string|null $approved_by
 * @property string|null $received_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Business $business
 * @property-read Outlet $fromOutlet
 * @property-read Outlet $toOutlet
 * @property-read User|null $requester
 * @property-read User|null $approver
 * @property-read User|null $receiver
 * @property-read Collection|StockTransferItem[] $items
 *
 * @mixin \Eloquent
 * @mixin IdeHelperStockTransfer
 */
class StockTransfer extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'from_outlet_id',
        'to_outlet_id',
        'transfer_number',
        'status',
        'notes',
        'requested_by',
        'approved_by',
        'received_by',
    ];

    protected array $sortable = [
        'transfer_number',
        'status',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function fromOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    public function toOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            function (Builder $q, $value) {
                $q->where(function ($sub) use ($value) {
                    $sub->where('transfer_number', 'ilike', "%{$value}%")
                        ->orWhereHas('items.inventoryItem', function ($itemQ) use ($value) {
                            $itemQ->where('name', 'ilike', "%{$value}%")
                                ->orWhere('sku', 'ilike', "%{$value}%");
                        });
                });
            }
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        )->when(
            $filters['from_outlet_id'] ?? false,
            fn (Builder $q, $value) => $q->where('from_outlet_id', $value)
        )->when(
            $filters['to_outlet_id'] ?? false,
            fn (Builder $q, $value) => $q->where('to_outlet_id', $value)
        )->when(
            $filters['outlet_id'] ?? \App\Helpers\SelectedOutlet::make()->currentId(),
            fn (Builder $q, $value) => $q->where(function (Builder $sub) use ($value) {
                $sub->where('from_outlet_id', $value)
                    ->orWhere('to_outlet_id', $value);
            })
        )->when(
            $filters['date_from'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('created_at', '>=', $value)
        )->when(
            $filters['date_to'] ?? false,
            fn (Builder $q, $value) => $q->whereDate('created_at', '<=', $value)
        );
    }
}
