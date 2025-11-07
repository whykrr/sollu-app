<?php

namespace App\Models;

use App\Enum\SubscriptionInvoice\Status;
use App\Trait\HasMerchant;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;

/**
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|SubscriptionPlan $plan
 * @property-read Collection|SubscriptionInvoiceItem[] $items
 * @property-read Collection|SubscriptionPayment[] $payments
 * @mixin IdeHelperSubscriptionInvoice
 */
class SubscriptionInvoice extends Model
{
    use HasFactory;
    use HasUuids;
    use HasMerchant;
    use SortableModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'merchant_id',
        'merchant_subscription_id',
        'subscription_plan_id',
        'note',
        'subtotal',
        'add_ons',
        'tax',
        'discount',
        'total',
        'due_date',
        'status',
        'period_end',
    ];

    protected $sortable = [
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => Status::class,
    ];

    public static function generateCode()
    {
        // contoh: INV-20250912-8F3K2X
        $prefix     = 'INV';
        $datePart   = now()->format('Ymd');
        $randomPart = Str::upper(Str::random(6));

        return "{$prefix}-SUBS-{$datePart}-{$randomPart}";
    }

    /**
     * Get the plan that owns the SubscriptionInvoice
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the subscription that owns the MerchantSubscriptions
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MerchantSubscriptions::class, 'merchant_subscription_id');
    }

    /**
     * Get the merchant that owns the Merchant
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Get all of the items for the SubscriptionInvoiceItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionInvoiceItem::class);
    }

    /**
     * Get all of the payments for the SubscriptionPayment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'invoice_id');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn ($builder, $value) => $builder->whereLike('code', "%{$value}%")
        )->when(
            $filters['status'] ?? false,
            fn (Builder $builder, $value) => $builder->where('status', '=', $value)
        );
    }
}
