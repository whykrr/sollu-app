<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Enums\FeatureEnum;
use App\Enums\InventoryCostingMethod;
use App\Models\Master\Product;
use App\Services\App\Inventory\InventorySodService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read Collection|BusinessType $type
 * @property-read Collection|Outlet[] $outlets
 * @property-read Collection|User[] $users
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
        'has_used_trial',
        'business_type_id',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
            'settings' => 'json',
            'has_used_trial' => 'boolean',
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
     * Find a business model by ID from cache.
     */
    public static function findCached(string $id): ?self
    {
        return Cache::remember("business:{$id}:detail", 3600, function () use ($id) {
            return static::query()->find($id);
        });
    }

    /**
     * Clear all cached business data and features for a given business ID.
     */
    public static function clearCache(string $businessId): void
    {
        Cache::forget("business:{$businessId}:detail");
        Cache::forget("business:{$businessId}:active_subscription");
        Cache::forget("business:{$businessId}:available_plan_features");
        Cache::forget("business:{$businessId}:active_plan_features");
    }

    /**
     * Clear cached active subscription for a given business ID.
     */
    public static function clearActiveSubscriptionCache(string $businessId): void
    {
        Cache::forget("business:{$businessId}:active_subscription");
    }

    /**
     * Memoized active subscription instance with plan and system features loaded.
     */
    protected ?Subscription $memoizedActiveSubscription = null;

    /**
     * Memoized available plan features.
     *
     * @var array<FeatureEnum>|null
     */
    protected ?array $memoizedAvailablePlanFeatures = null;

    /**
     * Memoized active plan features.
     *
     * @var array<FeatureEnum>|null
     */
    protected ?array $memoizedActivePlanFeatures = null;

    /**
     * Clear memoized subscription and feature caches on this model instance and cache store.
     */
    public function clearMemoizedFeatures(): void
    {
        $this->memoizedActiveSubscription = null;
        $this->memoizedAvailablePlanFeatures = null;
        $this->memoizedActivePlanFeatures = null;

        if ($this->id) {
            self::clearCache($this->id);
        }
    }

    protected static function booted(): void
    {
        static::saved(function (self $business) {
            self::clearCache($business->id);
            $business->clearMemoizedFeatures();
        });
        static::deleted(function (self $business) {
            self::clearCache($business->id);
            $business->clearMemoizedFeatures();
        });
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
                ->first(function (Subscription $sub) {
                    if ($sub->expired_at === null || Carbon::parse($sub->expired_at)->isFuture()) {
                        return true;
                    }
                    // Non-trial 3-day grace period
                    if (! $sub->is_trial && Carbon::parse($sub->expired_at)->gte(Carbon::now()->subDays(3))) {
                        return true;
                    }

                    return false;
                });

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

        $subscription = Cache::remember("business:{$this->id}:active_subscription", 3600, function () {
            return $this->subscriptions()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expired_at')
                        ->orWhere('expired_at', '>=', Carbon::now())
                        ->orWhere(function ($q) {
                            $q->where('is_trial', false)
                                ->where('expired_at', '>=', Carbon::now()->subDays(3));
                        });
                })
                ->latest()
                ->first();
        });

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
     * Get the active plan features for this business following the 3-tier hierarchy:
     * Level 1 (BusinessType) ∩ Level 2 (SubscriptionPlan) ∩ Level 3 (Merchant Personalization)
     *
     * @param  array<FeatureEnum>|null  $planFeatures
     * @return array<FeatureEnum>
     */
    public function activePlanFeatures(?array $planFeatures = null): array
    {
        if ($planFeatures === null && $this->memoizedActivePlanFeatures !== null) {
            return $this->memoizedActivePlanFeatures;
        }

        $shouldCache = $planFeatures === null;
        $cacheKey = "business:{$this->id}:active_plan_features";
        if ($shouldCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->memoizedActivePlanFeatures = $cached;
            }
        }

        // Level 2: Features granted by the subscription plan
        $planFeatures = $planFeatures ?? $this->getAvailablePlanFeatures();

        // Level 1: Features supported by the BusinessType
        $type = $this->relationLoaded('type')
            ? $this->type
            : ($this->business_type_id ? BusinessType::getAllCached()->firstWhere('id', $this->business_type_id) : null);

        $typeFeatures = $type ? $type->featureEnums() : null;

        // Base allowed features: intersection of Level 1 (BusinessType) and Level 2 (Plan)
        $allowedFeatures = is_array($type?->features) && $typeFeatures !== null
            ? array_values(array_filter($planFeatures, fn ($pf) => in_array($pf, $typeFeatures, true)))
            : $planFeatures;

        // Level 3: Merchant personalization
        // Personalization is only accessible if the plan includes FEATURE_PERSONALIZATION (Basic and above)
        $canPersonalize = in_array(FeatureEnum::FEATURE_PERSONALIZATION, $planFeatures, true);
        $userFeatures = $canPersonalize ? ($this->settings['active_features'] ?? null) : null;

        if (is_null($userFeatures)) {
            // Default for Micro plan or unconfigured personalization: entire allowed features
            $activeFeatures = $allowedFeatures;
        } else {
            // Merchant personalized: intersect with allowedFeatures
            $activeFeatures = [];
            foreach ($userFeatures as $featureString) {
                $featureEnum = $featureString instanceof FeatureEnum
                    ? $featureString
                    : FeatureEnum::tryFrom((string) $featureString);

                if ($featureEnum && in_array($featureEnum, $allowedFeatures, true)) {
                    $activeFeatures[] = $featureEnum;
                }
            }

            // Always ensure FEATURE_PERSONALIZATION remains active if supported by the plan/type
            // so a merchant cannot accidentally lock themselves out of the personalization page.
            if ($canPersonalize && in_array(FeatureEnum::FEATURE_PERSONALIZATION, $allowedFeatures, true) && ! in_array(FeatureEnum::FEATURE_PERSONALIZATION, $activeFeatures, true)) {
                $activeFeatures[] = FeatureEnum::FEATURE_PERSONALIZATION;
            }
        }

        if ($shouldCache) {
            Cache::put($cacheKey, $activeFeatures, 3600);
        }

        return $this->memoizedActivePlanFeatures = $activeFeatures;
    }

    /**
     * Check if business is currently in the 3-day grace period.
     */
    public function isInGracePeriod(): bool
    {
        $sub = $this->getActiveSubscriptionWithPlan();
        if (! $sub || $sub->is_trial || $sub->expired_at === null) {
            return false;
        }

        return $sub->expired_at->isPast() && $sub->expired_at->gte(Carbon::now()->subDays(3));
    }

    /**
     * Get remaining grace days (0, 1, 2, or 3).
     */
    public function graceDaysRemaining(): int
    {
        $sub = $this->getActiveSubscriptionWithPlan();
        if (! $sub || ! $this->isInGracePeriod()) {
            return 0;
        }

        $graceEnd = $sub->expired_at->copy()->addDays(3);

        return max(0, (int) ceil(Carbon::now()->diffInDays($graceEnd, false)));
    }

    /**
     * Get all available features granted by the business plan.
     *
     * @return array<FeatureEnum>
     */
    public function getAvailablePlanFeatures(): array
    {
        if ($this->memoizedAvailablePlanFeatures !== null) {
            return $this->memoizedAvailablePlanFeatures;
        }

        return $this->memoizedAvailablePlanFeatures = Cache::remember("business:{$this->id}:available_plan_features", 3600, function () {
            $activeSubscription = $this->getActiveSubscriptionWithPlan();

            if ($activeSubscription && $activeSubscription->plan) {
                return $activeSubscription->plan->activeFeatureEnums();
            }

            return [];
        });
    }

    /**
     * Check how many outlets are currently active.
     */
    public function activeOutletsCount(): int
    {
        return $this->outlets()->where('is_active', true)->count();
    }

    /**
     * Check if business can add another active outlet based on current plan quota.
     */
    public function canAddOutlet(): bool
    {
        $subscription = $this->getActiveSubscriptionWithPlan();

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        return $subscription->plan->allowsOutletCount($this->activeOutletsCount() + 1);
    }

    /**
     * Check if the business has explicitly personalized its active features.
     */
    public function isFeaturePersonalized(): bool
    {
        $planFeatures = $this->getAvailablePlanFeatures();
        $canPersonalize = in_array(FeatureEnum::FEATURE_PERSONALIZATION, $planFeatures, true);

        return $canPersonalize && isset($this->settings['active_features']) && is_array($this->settings['active_features']);
    }

    /**
     * Reset merchant personalized features back to default unconfigured state.
     */
    public function resetFeaturePersonalization(): void
    {
        $settings = $this->settings ?? [];
        unset($settings['active_features']);

        $this->settings = $settings;
        $this->save();

        $this->memoizedActivePlanFeatures = null;
        self::clearCache($this->id);
    }

    /**
     * Check if the business has a specific feature.
     */
    public function hasFeature(FeatureEnum $feature): bool
    {
        return in_array($feature, $this->activePlanFeatures(), true);
    }

    /**
     * Alias for hasFeature to support plan feature checks.
     */
    public function hasPlanFeature(FeatureEnum $feature): bool
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
    public function getCostingMethod(): InventoryCostingMethod
    {
        $raw = $this->settings['inventory_costing_method'] ?? InventoryCostingMethod::FIFO->value;

        return InventoryCostingMethod::tryFrom($raw) ?? InventoryCostingMethod::FIFO;
    }

    /**
     * Get the inventory segregation of duties (SoD) configuration.
     *
     * @return array<string, mixed>
     */
    public function getInventorySodSettings(): array
    {
        return app(InventorySodService::class)->getSettings($this);
    }

    /**
     * Check if inventory segregation of duties (SoD) is enabled globally.
     */
    public function isInventorySodEnabled(): bool
    {
        return app(InventorySodService::class)->isSodEnabled($this);
    }

    /**
     * Check if business owner is allowed to bypass SoD checks.
     */
    public function allowsOwnerSodBypass(): bool
    {
        return app(InventorySodService::class)->allowsOwnerBypass($this);
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
