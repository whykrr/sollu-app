<?php

namespace App\Models;

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
        });

        static::deleted(function ($setting) {
            Cache::forget("system_setting_{$setting->key}");
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
                'value' => $value,
                'group' => $group,
            ]
        );

        Cache::forget("system_setting_{$key}");

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
}
