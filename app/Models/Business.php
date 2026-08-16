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
use Illuminate\Support\Facades\Storage;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read Collection|BusinessType $type
 * @property-read Collection|Outlet[] $outlets
 * @property-read Collection|User[] $users
 * @property-read Collection|ProductVariation[] $product_variations
 * @property-read Collection|Product[] $products
 *
 * @mixin \Eloquent
 * @mixin IdeHelperBusiness
 */
class Business extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuids;
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
        'logo',
        'trial_end_at',
        'business_type_id',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
    }

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo
            ? Storage::url($this->logo)
            : null;
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    /**
     * Get all of the outlets for the Merchant
     */
    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /**
     * Get all of the users for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|<Collection|User[]>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all of the subscriptions for the Merchant
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all of the invoices for the Merchant
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all of the billing logs for the Merchant
     */
    public function billingLogs(): HasMany
    {
        return $this->hasMany(BillingLog::class);
    }

    /**
     * Get all of the product_variations for the Merchant
     */
    public function product_variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get all of the products for the Merchant
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the maximum number of outlets allowed for this business.
     */
    public function maxOutletsAllowed(): int
    {
        $activeSubscription = $this->subscriptions()
            ->where('status', 'active')
            ->first();

        if (! $activeSubscription || ! $activeSubscription->plan) {
            return 1;
        }

        return $activeSubscription->plan->max_outlet ?? 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
