<?php

namespace App\Models\Inventory;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read InventoryItem $inventoryItem
 * @property-read Outlet $outlet
 * @mixin \Eloquent
 */
class InventoryCostLayer extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'inventory_item_id',
        'outlet_id',
        'purchase_price',
        'qty_purchased',
        'qty_remaining',
        'reference_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'qty_purchased'  => 'decimal:4',
            'qty_remaining'  => 'decimal:4',
            'created_at'     => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
