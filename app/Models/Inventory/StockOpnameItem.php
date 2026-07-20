<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read StockOpname $stockOpname
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 */
class StockOpnameItem extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'stock_opname_id',
        'inventory_item_id',
        'system_qty',
        'actual_qty',
        'difference_qty',
    ];

    protected function casts(): array
    {
        return [
            'system_qty'     => 'decimal:4',
            'actual_qty'     => 'decimal:4',
            'difference_qty' => 'decimal:4',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
