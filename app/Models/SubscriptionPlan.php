<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection|MerchantOutletSubscription[] $transactions
 * @mixin IdeHelperSubscriptionPlan
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'status',
        'duration',
        'is_trial',
    ];

    /**
    * Get the attributes that should be cast.
    *
     * @return array<string, string>
    */
    protected function casts(): array
    {
        return [
            'is_trial' => 'boolean',
        ];
    }

    /**
     * Get all of the transactions for the SubscriptionPlan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(MerchantOutletSubscription::class);
    }
}
