<?php

namespace App\Models\Inventory;

use App\Models\Business;
use App\Models\Outlet;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Traits\HasQuantityFormatter;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Business $business
 * @property-read Outlet $outlet
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 */
class InventoryBalance extends Model
{
    use HasQuantityFormatter;

    use HasFactory;
    use HasUuids;
    use HasBusiness;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'inventory_item_id',
        'current_stock',
    ];

        protected $appends = [
        'current_stock_formatted',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    protected function currentStockFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->current_stock),
        );
    }
}
