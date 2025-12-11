<?php

namespace App\Models\Product;

use App\Models\MerchantType;
use App\Trait\HasMerchant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read ProductCategory|null $parent
 * @property-read Collection|ProductCategory[] $children
 * @property-read Collection|Product[] $products
 * @property-read Collection|MerchantType[] $merchantTypes
 * @mixin IdeHelperProductCategory
 */
class ProductCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSlug;
    use HasMerchant;

    protected $fillable = [
        'merchant_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'level',
    ];

    protected $casts = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id', 'id');
    }

    /**
     * The products that belong to the ProductCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category_product');
    }

    /**
     * @return BelongsToMany
     */
    public function merchantTypes(): BelongsToMany
    {
        return $this->belongsToMany(MerchantType::class);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function getFullPathAttribute(): string
    {
        $path   = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' -> ', $path);
    }
}
