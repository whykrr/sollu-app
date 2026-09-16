<?php

namespace App\Models;

use App\Enums\FeatureEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperSubscriptionPlan
 */
class SubscriptionPlan extends Model
{
    use HasFactory;
    use HasUuids;

    public const CACHE_KEY_ALL = 'system:subscription_plans:all';

    public const CACHE_KEY_ACTIVE = 'system:subscription_plans:active';

    protected $fillable = [
        'code',
        'name',
        'price_per_outlet',
        'max_outlet',
        'yearly_discount_percent',
        'features',
        'is_active',
        'is_public',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'price_per_outlet' => 'decimal:2',
            'yearly_discount_percent' => 'integer',
            'max_outlet' => 'integer',
            'features' => 'json',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function systemFeatures(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features', 'plan_id', 'feature_id')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Get all cached subscription plans with system features loaded.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    public static function getAllCached(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY_ALL, function () {
            return static::query()
                ->with(['systemFeatures'])
                ->orderBy('price_per_outlet', 'asc')
                ->get();
        });
    }

    /**
     * Get all active cached subscription plans.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    public static function getActiveCached(): Collection
    {
        return static::getAllCached()
            ->filter(fn (self $plan) => $plan->is_active)
            ->values();
    }

    /**
     * Find a subscription plan by code from cache.
     */
    public static function findByCodeCached(string $code): ?self
    {
        return static::getAllCached()->firstWhere('code', $code);
    }

    /**
     * Find a subscription plan by ID from cache.
     */
    public static function findCached(string $id): ?self
    {
        return static::getAllCached()->firstWhere('id', $id);
    }

    /**
     * Clear all subscription plan list caches.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        Cache::forget(self::CACHE_KEY_ACTIVE);
    }

    /**
     * Get active system feature codes for this plan.
     *
     * @return array<string>
     */
    public function activeFeatureCodes(): array
    {
        if ($this->relationLoaded('systemFeatures')) {
            return $this->systemFeatures->where('is_active', true)->pluck('code')->all();
        }

        return Cache::remember("plan:{$this->id}:active_features", 3600, function () {
            return $this->systemFeatures()->where('is_active', true)->pluck('code')->all();
        });
    }

    /**
     * Get active FeatureEnum instances for this plan.
     *
     * @return array<FeatureEnum>
     */
    public function activeFeatureEnums(): array
    {
        return collect($this->activeFeatureCodes())
            ->map(fn (string $code) => FeatureEnum::tryFrom($code))
            ->filter()
            ->values()
            ->all();
    }

    public function clearFeatureCache(): void
    {
        Cache::forget("plan:{$this->id}:active_features");
        static::clearCache();
    }

    protected static function booted(): void
    {
        static::saved(function (self $plan) {
            $plan->clearFeatureCache();
        });
        static::deleted(function (self $plan) {
            $plan->clearFeatureCache();
        });
    }
}
