<?php

namespace App\Models\Master;

use App\Models\Business;
use App\Models\Sales\Transaction;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasBusiness, HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
