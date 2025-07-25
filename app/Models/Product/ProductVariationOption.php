<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Collection|Product $product
 * @property-read Collection|ProductVariation $master
 * @mixin IdeHelperProductVariationOption
 */
class ProductVariationOption extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'product_variation_id',
    ];

    /**
     * Get the product that owns the ProductVariationOption
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the master  that owns the ProductVariationOption
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
