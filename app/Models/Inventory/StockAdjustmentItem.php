<?php

namespace App\Models\Inventory;

use App\Models\Traits\HasQuantityFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $stock_adjustment_id
 * @property string $inventory_item_id
 * @property \App\Enums\InventoryMovementType $movement_type
 * @property string $qty_change
 * @property string|null $unit_cost
 * @property string|null $stock_before
 * @property string|null $stock_after
 * @property string $description
 * 
 * @property-read \App\Models\Inventory\StockAdjustment $adjustment
 * @property-read \App\Models\Inventory\InventoryItem $inventoryItem
 */
class StockAdjustmentItem extends Model
{
    use HasFactory;
    use HasUuids;
    use HasQuantityFormatter;

    public $timestamps = false;

    protected $fillable = [
        'stock_adjustment_id',
        'inventory_item_id',
        'movement_type',
        'qty_change',
        'unit_cost',
        'stock_before',
        'stock_after',
        'description',
    ];

    protected $appends = [
        'qty_change_formatted',
        'stock_before_formatted',
        'stock_after_formatted',
    ];

    protected function casts(): array
    {
        return [
            'qty_change'   => 'decimal:4',
            'unit_cost'    => 'decimal:4',
            'stock_before' => 'decimal:4',
            'stock_after'  => 'decimal:4',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    protected function qtyChangeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatQuantity($this->qty_change),
        );
    }
    protected function stockBeforeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_before !== null ? $this->formatQuantity($this->stock_before) : null,
        );
    }
    protected function stockAfterFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_after !== null ? $this->formatQuantity($this->stock_after) : null,
        );
    }
}
