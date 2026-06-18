<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'price_per_outlet',
        'max_outlet',
        'yearly_discount_percent',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'json',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
