<?php

namespace App\Models;

use App\Enums\FeatureEnum;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_visible
 * @property int $sort_order
 * @property array<string>|null $features
 * @property-read Collection|Business[] $businesses
 * @mixin \Eloquent
 * @mixin IdeHelperBusinessType
 */
class BusinessType extends Model
{
    use HasFactory;
    use SortableModel;

    protected array $sortable = [
        'name',
        'code',
        'sort_order',
        'is_visible',
        'businesses_count',
    ];

    protected $fillable = [
        'code',
        'name',
        'is_visible',
        'sort_order',
        'features',
    ];

    public $timestamps = false;

    /**
     * Cache key for business types list.
     */
    public const CACHE_KEY = 'business_types:all';

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Get all cached business types ordered by sort_order.
     *
     * @return Collection<int, BusinessType>
     */
    public static function getAllCached()
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::orderBy('sort_order')->orderBy('name')->get();
        });
    }

    /**
     * Clear the business types cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Mengembalikan daftar instance FeatureEnum untuk jenis bisnis ini.
     *
     * @return array<FeatureEnum>
     */
    public function featureEnums(): array
    {
        if (! is_array($this->features)) {
            return [];
        }

        $enums = [];
        foreach ($this->features as $featureString) {
            $enum = FeatureEnum::tryFrom($featureString);
            if ($enum) {
                $enums[] = $enum;
            }
        }

        return $enums;
    }

    /**
     * Memeriksa apakah fitur tertentu didukung oleh jenis bisnis ini.
     */
    public function hasFeature(FeatureEnum $feature): bool
    {
        if (! is_array($this->features)) {
            return false;
        }

        return in_array($feature->value, $this->features, true);
    }

    /**
     * Array opsi [code => name] untuk form dropdown.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::getAllCached()
            ->pluck('name', 'code')
            ->toArray();
    }
}
