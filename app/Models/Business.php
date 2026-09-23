<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Models\Master\Product;
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
 * @property-read Collection|Product[] $products
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
            'status' => BusinessStatus::class,
            'settings' => 'json',
            'trial_end_at' => 'datetime',
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
     * Get all custom subscription plans assigned specifically to this Merchant
     */
    public function customSubscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'business_id');
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
     * Get all of the products for the Merchant
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Memoized active subscription instance with plan and system features loaded.
     */
    protected ?Subscription $memoizedActiveSubscription = null;

    /**
     * Memoized available plan features.
     *
     * @var array<\App\Enums\FeatureEnum>|null
     */
    protected ?array $memoizedAvailablePlanFeatures = null;

    /**
     * Memoized active plan features.
     *
     * @var array<\App\Enums\FeatureEnum>|null
     */
    protected ?array $memoizedActivePlanFeatures = null;

    /**
     * Clear memoized subscription and feature caches on this model instance.
     */
    public function clearMemoizedFeatures(): void
    {
        $this->memoizedActiveSubscription = null;
        $this->memoizedAvailablePlanFeatures = null;
        $this->memoizedActivePlanFeatures = null;
    }

    /**
     * Get the active subscription with loaded plan and system features.
     */
    public function getActiveSubscriptionWithPlan(): ?Subscription
    {
        if ($this->memoizedActiveSubscription !== null) {
            return $this->memoizedActiveSubscription;
        }

        if ($this->relationLoaded('subscriptions')) {
            $subscription = $this->subscriptions
                ->where('status', 'active')
                ->first();

            if ($subscription) {
                if (! $subscription->relationLoaded('plan')) {
                    $cachedPlan = $subscription->plan_id ? SubscriptionPlan::findCached($subscription->plan_id) : null;
                    if ($cachedPlan) {
                        $subscription->setRelation('plan', $cachedPlan);
                    } else {
                        $subscription->load(['plan.systemFeatures']);
                    }
                }

                return $this->memoizedActiveSubscription = $subscription;
            }
        }

        $subscription = $this->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            $cachedPlan = $subscription->plan_id ? SubscriptionPlan::findCached($subscription->plan_id) : null;
            if ($cachedPlan) {
                $subscription->setRelation('plan', $cachedPlan);
            } else {
                $subscription->load(['plan.systemFeatures']);
            }
        }

        return $this->memoizedActiveSubscription = $subscription;
    }

    /**
     * Get the active plan features for this business.
     *
     * @param  array<\App\Enums\FeatureEnum>|null  $planFeatures
     * @return array<\App\Enums\FeatureEnum>
     */
    public function activePlanFeatures(?array $planFeatures = null): array
    {
        if ($planFeatures === null && $this->memoizedActivePlanFeatures !== null) {
            return $this->memoizedActivePlanFeatures;
        }

        $planFeatures = $planFeatures ?? $this->getAvailablePlanFeatures();

        // Get user personalized features if exists
        $userFeatures = $this->settings['active_features'] ?? null;

        if (is_null($userFeatures)) {
            // Fallback to BusinessType defaults without triggering extra lazy SQL queries
            $type = $this->relationLoaded('type')
                ? $this->type
                : ($this->business_type_id ? BusinessType::getAllCached()->firstWhere('id', $this->business_type_id) : null);

            $userFeatures = $type?->features ?? array_map(fn ($f) => $f->value, $planFeatures);
        }

        // Map strings to FeatureEnum and intersect with plan features
        $activeFeatures = [];
        foreach ($userFeatures as $featureString) {
            $featureEnum = $featureString instanceof \App\Enums\FeatureEnum
                ? $featureString
                : \App\Enums\FeatureEnum::tryFrom((string) $featureString);

            if ($featureEnum && in_array($featureEnum, $planFeatures, true)) {
                $activeFeatures[] = $featureEnum;
            }
        }

        return $this->memoizedActivePlanFeatures = $activeFeatures;
    }

    /**
     * Get all available features granted by the business plan.
     *
     * @return array<\App\Enums\FeatureEnum>
     */
    public function getAvailablePlanFeatures(): array
    {
        if ($this->memoizedAvailablePlanFeatures !== null) {
            return $this->memoizedAvailablePlanFeatures;
        }

        $activeSubscription = $this->getActiveSubscriptionWithPlan();

        if ($activeSubscription && $activeSubscription->plan) {
            return $this->memoizedAvailablePlanFeatures = $activeSubscription->plan->activeFeatureEnums();
        }

        $isTrial = $this->trial_end_at ? \Carbon\Carbon::parse($this->trial_end_at)->isFuture() : false;

        if ($isTrial) {
            $trialPlan = SubscriptionPlan::findByCodeCached(\App\Enums\PlanEnum::MICRO->value);

            return $this->memoizedAvailablePlanFeatures = ($trialPlan ? $trialPlan->activeFeatureEnums() : []);
        }

        return $this->memoizedAvailablePlanFeatures = [];
    }

    /**
     * Check if the business has a specific feature.
     */
    public function hasFeature(\App\Enums\FeatureEnum $feature): bool
    {
        return in_array($feature, $this->activePlanFeatures(), true);
    }

    /**
     * Alias for hasFeature to support plan feature checks.
     */
    public function hasPlanFeature(\App\Enums\FeatureEnum $feature): bool
    {
        return $this->hasFeature($feature);
    }

    /**
     * Check if the business has explicitly configured an inventory costing method.
     */
    public function isCostingMethodConfigured(): bool
    {
        return isset($this->settings['inventory_costing_method']) && ! empty($this->settings['inventory_costing_method']);
    }

    /**
     * Get the active inventory costing method (FIFO or Moving Average).
     */
    public function getCostingMethod(): \App\Enums\InventoryCostingMethod
    {
        $raw = $this->settings['inventory_costing_method'] ?? \App\Enums\InventoryCostingMethod::FIFO->value;

        return \App\Enums\InventoryCostingMethod::tryFrom($raw) ?? \App\Enums\InventoryCostingMethod::FIFO;
    }

    /**
     * Get the inventory segregation of duties (SoD) configuration.
     *
     * @return array<string, mixed>
     */
    public function getInventorySodSettings(): array
    {
        return app(\App\Services\App\Inventory\InventorySodService::class)->getSettings($this);
    }

    /**
     * Check if inventory segregation of duties (SoD) is enabled globally.
     */
    public function isInventorySodEnabled(): bool
    {
        return app(\App\Services\App\Inventory\InventorySodService::class)->isSodEnabled($this);
    }

    /**
     * Check if business owner is allowed to bypass SoD checks.
     */
    public function allowsOwnerSodBypass(): bool
    {
        return app(\App\Services\App\Inventory\InventorySodService::class)->allowsOwnerBypass($this);
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
