<?php

namespace App\Models\Product;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @package App\Models\Product
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|ProductType $type
 * @property-read Collection|ProductUnit $unit
 * @property-read Collection|ProductCategory[] $categories
 * @property-read Collection|ProductCombination[] $combinations
 * @property-read Collection|ProductVariation[] $variations
 * @property-read Collection|ProductVariationOptionValue[] $variation_values
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_id',
        'product_type_id',
        'name',
        'description',
        'base_price',
        'product_unit_id',
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
     * Get the merchant that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the type that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the unit that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * The categories that belong to the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'category_product');
    }

    /**
     * Get all of the combinations for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function combinations(): HasMany
    {
        return $this->hasMany(ProductCombination::class);
    }

    /**
     * Get all of the variations for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class);
    }

    /**
     * Get all of the variation_values for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variation_values(): HasMany
    {
        return $this->hasMany(ProductVariationOptionValue::class, 'product_id');
    }
}
