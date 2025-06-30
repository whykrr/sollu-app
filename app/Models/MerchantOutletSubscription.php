<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantOutletSubscription extends Model
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
        'outlet_id',
        'subscription_plans_id',
        'start_date',
        'end_date',
        'status',
    ];


    /**
     * Get the subscription_plan that owns the MerchantOutletSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription_plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
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

    /**
     * Get the outlet that owns the MerchantOutletSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

}
