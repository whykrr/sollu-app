<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'qty' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'unit_cogs' => 'decimal:4',
            'cogs_amount' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductItem, $this>
     */
    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
