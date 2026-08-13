<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasBusiness;
    use HasUuids;
    use SoftDeletes;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'product_category_id',
        'product_type',
        'has_variant',
        'has_modifier',
        'has_recipe',
        'track_inventory',
        'code',
        'name',
        'description',
        'image_url',
        'sort_order',
        'is_show',
        'sellable',
        'purchasable',
    ];

    protected $casts = [
        'has_variant' => 'boolean',
        'has_modifier' => 'boolean',
        'has_recipe' => 'boolean',
        'track_inventory' => 'boolean',
        'is_show' => 'boolean',
        'sellable' => 'boolean',
        'purchasable' => 'boolean',
    ];

    protected $appends = [
        'cover_image_url',
    ];

    public function getCoverImageUrlAttribute()
    {
        return $this->image_url ? \Illuminate\Support\Facades\Storage::url($this->image_url) : null;
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function variantGroups()
    {
        return $this->hasMany(VariantGroup::class)->orderBy('sort_order');
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_groups');
    }

    public function recipeVersions()
    {
        return $this->hasMany(RecipeVersion::class)->orderByDesc('version_number');
    }

    public function activeRecipe()
    {
        return $this->hasOne(RecipeVersion::class)->where('is_active', true);
    }

    public function bundleItems()
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function componentOf()
    {
        return $this->hasMany(ProductBundleItem::class, 'component_product_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function outlets()
    {
        return $this->belongsToMany(\App\Models\Outlet::class, 'outlet_product')
            ->withPivot('is_enabled', 'is_available');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when(
                $filters['category'] ?? false,
                fn ($builder, $value) => $builder->whereHas('category', function ($q) use ($value) {
                    $q->where('id', $value)->orWhere('parent_id', $value);
                })
            )
            ->when(
                $filters['outlet'] ?? false,
                fn ($builder, $value) => $builder->whereHas('outlets', function ($q) use ($value) {
                    $q->where('outlet_id', $value);
                })
            )
            ->when(
                $filters['search'] ?? false,
                fn ($builder, $value) => $builder->where(function ($q) use ($value) {
                    $q->whereLike('name', "%{$value}%")->orWhereLike('code', "%{$value}%");
                })
            )->when(
                $filters['is_deleted'] ?? false,
                fn (Builder $builder, $value) => $builder->withTrashed()
            );

    }
}
