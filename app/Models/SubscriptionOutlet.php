<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOutlet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subscription_id',
        'outlet_id',
        'activated_at',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
