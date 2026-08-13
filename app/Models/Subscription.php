<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSubscription
 */
class Subscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'plan_id',
        'status',
        'billing_cycle',
        'started_at',
        'expired_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expired_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function subscriptionOutlets(): HasMany
    {
        return $this->hasMany(SubscriptionOutlet::class);
    }
}
