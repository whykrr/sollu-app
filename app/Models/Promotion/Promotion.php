<?php

namespace App\Models\Promotion;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\User;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $table = 'promotions';

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'application_mode',
        'promo_code',
        'target_scope',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_subtotal',
        'min_quantity',
        'applies_to_all_outlets',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'days_of_week',
        'status',
        'published_by',
        'published_at',
        'created_by',
    ];

    protected $sortable = [
        'name',
        'promo_code',
        'application_mode',
        'target_scope',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'status',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): PromotionFactory
    {
        return PromotionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'application_mode' => PromotionApplicationMode::class,
            'target_scope' => PromotionTargetScope::class,
            'discount_type' => PromotionDiscountType::class,
            'status' => PromotionStatus::class,
            'discount_value' => 'float',
            'max_discount_amount' => 'float',
            'min_subtotal' => 'float',
            'min_quantity' => 'float',
            'applies_to_all_outlets' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'days_of_week' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'promotion_outlets', 'promotion_id', 'outlet_id')
            ->using(PromotionOutlet::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'promotion_categories', 'promotion_id', 'category_id')
            ->using(PromotionCategory::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products', 'promotion_id', 'product_id')
            ->using(PromotionProduct::class);
    }

    public function productItems(): BelongsToMany
    {
        return $this->belongsToMany(ProductItem::class, 'promotion_product_items', 'promotion_id', 'product_item_id')
            ->using(PromotionProductItem::class);
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
        return $query
            ->where('status', PromotionStatus::Active->value)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString());
    }

    public function scopeFilters(Builder $query, array $filters): Builder
    {
        $target = $filters['target_scope'] ?? $filters['target'] ?? null;
        $type = $filters['discount_type'] ?? $filters['type'] ?? $filters['promo_type'] ?? null;
        $mode = $filters['application_mode'] ?? $filters['mode'] ?? null;

        return $query
            ->when(
                $filters['search'] ?? false,
                fn (Builder $q, string $search) => $q->where(function (Builder $sub) use ($search) {
                    $sub->whereLike('name', "%{$search}%")
                        ->orWhereLike('promo_code', "%{$search}%");
                })
            )
            ->when(
                $filters['status'] ?? false,
                fn (Builder $q, $status) => $q->where('status', $status instanceof PromotionStatus ? $status->value : $status)
            )
            ->when(
                $target,
                fn (Builder $q, $value) => $q->where('target_scope', $value instanceof PromotionTargetScope ? $value->value : $value)
            )
            ->when(
                $type,
                fn (Builder $q, $value) => $q->where('discount_type', $value instanceof PromotionDiscountType ? $value->value : $value)
            )
            ->when(
                $mode,
                fn (Builder $q, $value) => $q->where('application_mode', $value instanceof PromotionApplicationMode ? $value->value : $value)
            )
            ->when(
                $filters['outlet'] ?? $filters['outlet_id'] ?? false,
                fn (Builder $q, string $outletId) => $q->where(function (Builder $sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn (Builder $o) => $o->where('outlets.id', $outletId));
                })
            );
    }
}
