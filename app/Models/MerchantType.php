<?php

namespace App\Models;

use App\Casts\SettingsCast;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Collection|Merchant[] $merchants
 * @property Collection|ProductCategory[] $productCategories
 * @mixin IdeHelperMerchantType
 */
class MerchantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_settings' => SettingsCast::class,
        ];
    }

    /**
     * @return HasMany
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    /**
     * @return BelongsToMany
     */
    public function productCategories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }

}
