<?php

namespace App\Models;

use App\Models\Product\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 * @property Collection|Business[] $merchants
 * @property Collection|ProductCategory[] $productCategories
 * @mixin \Eloquent
 */
class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @return HasMany
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * @return BelongsToMany
     */
    public function productCategories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }

}
