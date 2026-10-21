<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
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
        'is_trial',
        'started_at',
        'expired_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'status' => SubscriptionStatus::class,
            'is_trial' => 'boolean',
            'expired_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function scopeTrial(Builder $query): Builder
    {
        return $query->where('is_trial', true);
    }

    public function scopeActiveValid(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });
    }

    public function isTrial(): bool
    {
        return (bool) $this->is_trial;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && ($this->expired_at === null || $this->expired_at->isFuture());
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
