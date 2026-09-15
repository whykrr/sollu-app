<?php

namespace App\Models;

use App\Enums\FeatureEnum;
use Illuminate\Database\Eloquent\Builder;
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
    }

    protected static function booted(): void
    {
        static::saved(fn (self $plan) => $plan->clearFeatureCache());
        static::deleted(fn (self $plan) => $plan->clearFeatureCache());
    }
}
