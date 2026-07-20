<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'qty_ordered',
        'qty_received',
        'purchase_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered'    => 'decimal:4',
            'qty_received'   => 'decimal:4',
            'purchase_price' => 'decimal:2',
            'subtotal'       => 'decimal:2',
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
}
