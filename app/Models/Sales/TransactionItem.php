<?php

namespace App\Models\Sales;

use App\Models\Master\Product;
use App\Models\Master\VariantGroupOption;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'variant_group_option_id',
        'product_name',
        'price',
        'qty',
        'discount_amount',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'qty' => 'float',
            'discount_amount' => 'float',
            'subtotal' => 'float',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantGroupOption(): BelongsTo
    {
        return $this->belongsTo(VariantGroupOption::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(TransactionItemModifier::class);
    }
}
