<?php

namespace App\Models;

use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read Collection|MerchantType $type
 * @property-read Collection|Outlet[] $outlets
 * @property-read Collection|User[] $users
 * @property-read Collection|MerchantOutletSubscription $subscriptions
 * @property-read Collection|ProductVariation[] $product_variations
 * @property-read Collection|Product[] $products
 * @mixin IdeHelperMerchant
 */
class Merchant extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSlug;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'logo_url',
        'already_free_trial',
        'merchant_type_id',
        'settings',
    ];

    /**
    * Get the attributes that should be cast.
    *
     * @return array<string, string>
    */
    protected function casts(): array
    {
        return [
            'already_free_trial' => 'boolean',
            'settings'           => 'json',
        ];
    }

    /**
     * Get the industry that owns the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MerchantType::class);
    }

    /**
     * Get all of the outlets for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /**
     * Get all of the users for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all of the subscriptions for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantOutletSubscription::class);
    }

    /**
     * Get all of the product_variations for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get all of the products for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @inheritDoc
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
