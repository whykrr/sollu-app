<?php

namespace App\Models\Inventory;

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
            fn (Builder $q, $value) => $q->whereLike('transfer_number', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn (Builder $q, $value) => $q->where('status', $value)
        );
    }
}
