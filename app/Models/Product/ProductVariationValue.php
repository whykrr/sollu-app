<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection|ProductVariation $variation
 * @property-read Collection|ProductVariationOptionValue[] $product_variation_option_values
 * @mixin IdeHelperProductVariationValue
 */
class ProductVariationValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_variation_id',
        'name',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];


    /**
     * Get the variation that owns the ProductvariationValue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    /**
     * Get all of the product_variation_values for the ProductVariationOptionValue
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_variation_option_values(): HasMany
    {
        return $this->hasMany(ProductVariationOptionValue::class, 'product_variation_value_id');
    }
}
