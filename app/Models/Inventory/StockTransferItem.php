<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read StockTransfer $stockTransfer
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 */
class StockTransferItem extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'stock_transfer_id',
        'inventory_item_id',
        'qty',
        'qty_received',
    ];

    protected function casts(): array
    {
        return [
            'qty'          => 'decimal:4',
            'qty_received' => 'decimal:4',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
