<?php

namespace App\Models;

use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Enums\PromoType;
use App\Models\Master\Product;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promo extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'promo_type',
        'target_type',
        'discount_value',
        'max_discount',
        'applies_to_all_outlets',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'published_by',
        'published_at',
        'created_by',
    ];

    protected $sortable = [
        'name',
        'start_date',
        'end_date',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'promo_type' => PromoType::class,
            'target_type' => PromoTarget::class,
            'status' => PromoStatus::class,
            'discount_value' => 'float',
            'max_discount' => 'float',
            'applies_to_all_outlets' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'published_at' => 'datetime',
        ];
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'promo_outlets', 'promo_id', 'outlet_id')
            ->using(PromoOutlet::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promo_products', 'promo_id', 'product_id')
            ->using(PromoProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PromoStatus::Active->value)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopeFilters(Builder $query, array $filters): Builder
    {
        return $query->when(
            $filters['search'] ?? false,
            fn ($q, $value) => $q->whereLike('name', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn ($q, $value) => $q->where('status', $value)
        )->when(
            $filters['target'] ?? false,
            fn ($q, $value) => $q->where('target_type', $value)
        )->when(
            $filters['type'] ?? false,
            fn ($q, $value) => $q->where('promo_type', $value)
        )->when(
            $filters['outlet'] ?? false,
            fn ($q, $value) => $q->where(function ($q) use ($value) {
                $q->whereHas('outlets', fn ($q) => $q->where('outlets.id', $value))
                    ->orWhere('applies_to_all_outlets', true);
            })
        );
    }
}
