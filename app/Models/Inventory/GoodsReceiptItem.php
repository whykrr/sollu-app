<?php

namespace App\Models\Inventory;

use App\Models\Traits\HasQuantityFormatter;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read GoodsReceipt $goodsReceipt
 * @property-read PurchaseOrderItem|null $purchaseOrderItem
 * @property-read InventoryItem $inventoryItem
 * @property-read Uom|null $uom
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseReturnItem> $purchaseReturnItems
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
        'returned_purchase_qty',
        'remaining_returnable_qty',
        'purchase_unit_cost',
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

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'goods_receipt_item_id');
    }

    // ── Accessors & Formats ───────────────────────────────────────

    protected function returnedPurchaseQty(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('purchaseReturnItems')) {
                    return (float) $this->purchaseReturnItems
                        ->where(function ($item) {
                            return ! $item->purchaseReturn || $item->purchaseReturn->status !== \App\Enums\PurchaseReturnStatus::Voided;
                        })
                        ->sum('return_purchase_qty');
                }

                return (float) $this->purchaseReturnItems()
                    ->whereHas('purchaseReturn', function ($query) {
                        $query->where('status', '!=', \App\Enums\PurchaseReturnStatus::Voided->value);
                    })
                    ->sum('return_purchase_qty');
            }
        );
    }

    protected function remainingReturnableQty(): Attribute
    {
        return Attribute::make(
            get: function () {
                $status = $this->relationLoaded('goodsReceipt')
                    ? $this->goodsReceipt?->status
                    : $this->goodsReceipt()->value('status');

                if ($status === \App\Enums\GoodsReceiptStatus::Voided || $status === 'voided') {
                    return 0.0;
                }

                return max(0, (float) $this->received_purchase_qty - (float) $this->returned_purchase_qty);
            }
        );
    }

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

    /**
     * Harga beli efektif per satuan pembelian (Purchase UOM).
     */
    protected function purchaseUnitCost(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ((float) $this->received_purchase_qty > 0) {
                    return (float) ($this->total_cost / $this->received_purchase_qty);
                }

                return (float) ($this->unit_cost * ($this->conversion_factor ?: 1));
            }
        );
    }
}
