<?php

namespace App\Models\Master;

use App\Models\Outlet;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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

    protected array $sortable = [
        'name',
        'code',
        'product_type',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'cover_image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_variant' => 'boolean',
            'has_modifier' => 'boolean',
            'has_recipe' => 'boolean',
            'track_inventory' => 'boolean',
            'is_show' => 'boolean',
            'sellable' => 'boolean',
            'purchasable' => 'boolean',
        ];
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->image_url ? Storage::url($this->image_url) : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function variantGroups(): HasMany
    {
        return $this->hasMany(VariantGroup::class)->orderBy('sort_order');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_groups');
    }

    public function recipeVersions(): HasMany
    {
        return $this->hasMany(RecipeVersion::class)->orderByDesc('version_number');
    }

    public function activeRecipe(): HasOne
    {
        return $this->hasOne(RecipeVersion::class)->where('is_active', true);
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function componentOf(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'component_product_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'outlet_product')
            ->withPivot('is_enabled', 'is_available');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        $outletFilter = $filters['outlet'] ?? \App\Helpers\SelectedOutlet::make()->currentId();

        return $builder
            ->when(
                $filters['category'] ?? false,
                fn ($builder, $value) => $builder->whereHas('category', function ($q) use ($value) {
                    $q->where('id', $value)->orWhere('parent_id', $value);
                })
            )
            ->when(
                $outletFilter,
                fn ($builder, $value) => $builder->whereHas('outlets', function ($q) use ($value) {
                    $q->where('outlets.id', $value)
                        ->where('outlet_product.is_enabled', true);
                })
            )
            ->when(
                $filters['product_type'] ?? false,
                fn ($builder, $value) => $builder->where('product_type', $value)
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
