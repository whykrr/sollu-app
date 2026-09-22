<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperFeature
 */
class Feature extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'module',
        'group',
        'group_label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_features', 'feature_id', 'plan_id')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Get all active features cached.
     *
     * @return Collection<int, Feature>
     */
    public static function getAllCached(): Collection
    {
        return Cache::rememberForever('system:features:all', function () {
            return static::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get all active features grouped by group_label for UI.
     *
     * @return array<string, array<int, array{value: string, label: string, description: string}>>
     */
    public static function grouped(): array
    {
        $features = static::getAllCached()->where('is_active', true);
        $groups = [];

        foreach ($features as $feature) {
            $groupName = $feature->group_label ?: ($feature->group ?: 'Lainnya');
            $groups[$groupName][] = [
                'value' => $feature->code,
                'label' => $feature->name,
                'description' => $feature->description ?? '',
            ];
        }

        return $groups;
    }

    /**
     * Get key-value options [code => name] of active features.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::getAllCached()
            ->where('is_active', true)
            ->pluck('name', 'code')
            ->toArray();
    }

    public static function clearCache(): void
    {
        Cache::forget('system:features:all');
        SubscriptionPlan::clearCache();
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
