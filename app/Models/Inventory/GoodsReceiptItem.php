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
 * @property-read GoodsReceipt $goodsReceipt
 * @property-read PurchaseOrderItem|null $purchaseOrderItem
 * @property-read InventoryItem $inventoryItem
 * @property-read Uom|null $uom
 *
 * @mixin \Eloquent
 * @mixin IdeHelperGoodsReceiptItem
 */
class GoodsReceiptItem extends Model
{
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'inventory_item_id',
        'uom_id',
        'received_purchase_qty',
        'conversion_factor',
        'received_inventory_qty',
        'unit_cost',
        'total_cost',
    ];

    protected $appends = [
        'received_purchase_qty_formatted',
        'received_inventory_qty_formatted',
        'conversion_factor_formatted',
    ];

    protected function casts(): array
    {
        return [
            'received_purchase_qty' => 'float',
            'conversion_factor' => 'float',
            'received_inventory_qty' => 'float',
            'unit_cost' => 'float',
            'total_cost' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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

    protected function receivedPurchaseQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->received_purchase_qty),
        );
    }

    protected function receivedInventoryQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->received_inventory_qty),
        );
    }

    protected function conversionFactorFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->conversion_factor),
        );
    }
}
