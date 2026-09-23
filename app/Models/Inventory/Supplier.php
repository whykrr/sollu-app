<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Business $business
 * @property-read Collection|InventoryItem[] $inventoryItems
 * @property-read Collection|PurchaseOrder[] $purchaseOrders
 * @mixin \Eloquent
 * @mixin IdeHelperSupplier
 */
class Supplier extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'return_period_days',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'name',
        'phone',
        'email',
        'address',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'return_period_days' => 'integer',
        ];
    }

    /**
     * Dapatkan masa retur efektif dalam satuan hari (default: 7 hari).
     */
    public function getEffectiveReturnPeriodDays(): int
    {
        return $this->return_period_days !== null && $this->return_period_days >= 0
            ? (int) $this->return_period_days
            : 7;
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'supplier_inventory_items')
            ->withPivot('last_purchase_price');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn (Builder $q, $value) => $q->where(function ($q) use ($value) {
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('email', "%{$value}%")
                    ->orWhereLike('address', "%{$value}%")
                    ->orWhereLike('phone', "%{$value}%");
            })
        )->when(
            isset($filters['is_active']) && $filters['is_active'] !== '',
            fn (Builder $q) => $q->where('is_active', $filters['is_active'])
        );
    }
}
