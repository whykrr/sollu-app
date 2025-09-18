<?php

namespace App\Models;

use App\Models\Product\Product;
use App\Trait\HasMerchant;
use App\Trait\SortableModel;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Collection|Product[] $products
 * @mixin IdeHelperUnit
 */
class Unit extends Model
{
    use HasFactory;
    use HasMerchant;
    use SortableModel;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_id',
        'name',
        'symbol',
        'description',
    ];

    protected $sortable = [
        'name',
        'symbol',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }

    /**
     * Get all of the products for the ProductUnit
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the merchant that owns the Unit
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn ($builder, $value) => $builder->where(function (Builder $q) use ($value) {
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('symbol', "%{$value}%")
                    ->orWhereLike('description', "%{$value}%");
            })
        )->when(
            $filters['status'] ?? false,
            fn (Builder $builder, $value) => ($value == 'archived')
            ? $builder->onlyTrashed()
            : $builder->withTrashed()
        );
    }
}
