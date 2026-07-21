<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Traits\HasQuantityFormatter;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read StockOpname $stockOpname
 * @property-read InventoryItem $inventoryItem
 * @mixin \Eloquent
 */
class StockOpnameItem extends Model
{
    use HasQuantityFormatter;

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

        protected $appends = [
        'system_qty_formatted',
        'actual_qty_formatted',
        'difference_qty_formatted',
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

    protected function systemQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->system_qty),
        );
    }
    protected function actualQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->actual_qty),
        );
    }
    protected function differenceQtyFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->difference_qty),
        );
    }
}
