<?php

namespace App\Models\Inventory;

use App\Models\Traits\HasQuantityFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read StockTransfer $stockTransfer
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 * @mixin IdeHelperStockTransferItem
 */
class StockTransferItem extends Model
{
    use HasFactory;
    use HasQuantityFormatter;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'stock_transfer_id',
        'inventory_item_id',
        'qty',
        'qty_received',
    ];

    protected $appends = [
        'qty_formatted',
        'qty_received_formatted',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'float',
            'qty_received' => 'float',
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

    protected function qtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty),
        );
    }

    protected function qtyReceivedFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty_received),
        );
    }
}
