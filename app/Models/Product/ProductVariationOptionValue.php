<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Collection|Product $product
 * @property-read Collection|ProductVariationOption $variant_option
 * @property-read Collection|ProductVariationValue $master
 * @mixin IdeHelperProductVariationOptionValue
 */
class ProductVariationOptionValue extends Model
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
        'product_variation_option_id',
        'product_variation_value_id',
    ];

    /**
     * Get the product that owns the ProductVariationOptionValue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant_option that owns the ProductVariationOptionValue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant_option(): BelongsTo
    {
        return $this->belongsTo(ProductVariationOption::class, 'product_variation_option_id');
    }

    /**
     * Get the master that owns the ProductVariationOptionValue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(ProductVariationValue::class, 'product_variation_value_id');
    }
}
