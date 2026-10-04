<?php

namespace App\Models;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperSystemSetting
 */
class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget("system_setting_{$setting->key}");
            if ($setting->key === 'trial_features') {
                Cache::forget('system:trial_features:enums');
            }
        });

        static::deleted(function ($setting) {
            Cache::forget("system_setting_{$setting->key}");
            if ($setting->key === 'trial_features') {
                Cache::forget('system:trial_features:enums');
            }
        });
    }

    /**
     * Get system setting value by key, with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", 86400, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting !== null ? $setting->value : $default;
        });
    }

    /**
     * Set system setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
            ]
        );

        Cache::forget("system_setting_{$key}");
        if ($key === 'trial_features') {
            Cache::forget('system:trial_features:enums');
        }

        return $setting;
    }

    /**
     * Determine if Midtrans payment is enabled globally.
     */
    public static function isMidtransEnabled(): bool
    {
        $value = self::get('midtrans_payment_enabled', false);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get default trial duration in days.
     */
    public static function getTrialDurationDays(): int
    {
        $value = self::get('trial_default_duration_days', 14);

        return max(1, (int) $value);
    }

    /**
     * Get active trial FeatureEnum instances from cache or fallback to Basic plan.
     *
     * @return array<FeatureEnum>
     */
    public static function getTrialFeatureEnumsCached(): array
    {
        return Cache::rememberForever('system:trial_features:enums', function () {
            $raw = self::get('trial_features', null);

            if ($raw === null) {
                $basicPlan = SubscriptionPlan::findByCodeCached(PlanEnum::BASIC->value);

                return $basicPlan ? $basicPlan->activeFeatureEnums() : [];
            }

            $codes = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

            return collect($codes)
                ->map(fn (string $code) => FeatureEnum::tryFrom($code))
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * Clear all trial configuration caches.
     */
    public static function clearTrialCache(): void
    {
        Cache::forget('system_setting_trial_features');
        Cache::forget('system_setting_trial_default_duration_days');
        Cache::forget('system:trial_features:enums');
    }
}
