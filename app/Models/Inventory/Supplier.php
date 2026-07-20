<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Trait\HasBusiness;
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
 */
class Supplier extends Model
{
    use HasFactory;
    use HasUuids;
    use HasBusiness;
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
                    ->orWhereLike('phone', "%{$value}%");
            })
        )->when(
            $filters['is_active'] ?? null,
            fn (Builder $q, $value) => $q->where('is_active', $value)
        );
    }
}
