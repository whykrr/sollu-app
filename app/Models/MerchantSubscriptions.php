<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Collection|SubscriptionPlan $subscription_plan
 * @property-read Collection|Merchant $merchant
 * @mixin IdeHelperMerchantSubscriptions
 */
class MerchantSubscriptions extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_id',
        'subscription_plans_id',
        'start_date',
        'end_date',
    ];


    /**
     * Get the subscription_plan that owns the MerchantOutletSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plans_id');
    }

    /**
     * Get the merchant that owns the MerchantOutletSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

}
