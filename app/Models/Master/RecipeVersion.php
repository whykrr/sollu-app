<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperRecipeVersion
 */
class RecipeVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'product_item_id',
        'version_number',
        'is_active',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }
}
