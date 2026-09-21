<?php

namespace App\Models\Inventory;

use App\Models\Traits\HasQuantityFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read InventoryItem $inventoryItem
 *
 * @mixin \Eloquent
 * @mixin IdeHelperPurchaseOrderItem
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'uom_id',
        'qty_ordered',
        'qty_received',
        'purchase_price',
        'discount_amount',
        'tax_amount',
        'subtotal',
        'conversion_factor',
        'converted_qty',
    ];

    protected $appends = [
        'qty_ordered_formatted',
        'qty_received_formatted',
        'converted_qty_formatted',
        'outstanding_qty',
        'outstanding_qty_formatted',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered' => 'float',
            'qty_received' => 'float',
            'purchase_price' => 'float',
            'discount_amount' => 'float',
            'tax_amount' => 'float',
            'subtotal' => 'float',
            'conversion_factor' => 'float',
            'converted_qty' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uom::class);
    }

    public function goodsReceiptItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    // ── Accessors & Formats ───────────────────────────────────────

    protected function outstandingQty(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->qty_ordered - (float) $this->qty_received),
        );
    }

    protected function outstandingQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity(max(0, (float) $this->qty_ordered - (float) $this->qty_received)),
        );
    }

    protected function qtyOrderedFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty_ordered),
        );
    }

    protected function qtyReceivedFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty_received),
        );
    }

    protected function convertedQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->converted_qty),
        );
    }
}
