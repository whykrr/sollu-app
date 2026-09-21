<?php

namespace App\Models\Inventory;

use App\Models\Traits\HasQuantityFormatter;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read PurchaseReturn $purchaseReturn
 * @property-read InventoryItem $inventoryItem
 * @property-read Uom|null $uom
 *
 * @mixin \Eloquent
 * @mixin IdeHelperPurchaseReturnItem
 */
class PurchaseReturnItem extends Model
{
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'purchase_return_id',
        'inventory_item_id',
        'uom_id',
        'return_purchase_qty',
        'conversion_factor',
        'return_inventory_qty',
        'unit_cost',
        'subtotal',
    ];

    protected $appends = [
        'return_purchase_qty_formatted',
        'return_inventory_qty_formatted',
        'conversion_factor_formatted',
    ];

    protected function casts(): array
    {
        return [
            'return_purchase_qty' => 'float',
            'conversion_factor' => 'float',
            'return_inventory_qty' => 'float',
            'unit_cost' => 'float',
            'subtotal' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    // ── Accessors & Formats ───────────────────────────────────────

    protected function returnPurchaseQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->return_purchase_qty),
        );
    }

    protected function returnInventoryQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->return_inventory_qty),
        );
    }

    protected function conversionFactorFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->conversion_factor),
        );
    }
}
