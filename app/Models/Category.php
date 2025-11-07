<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
    ];

    /**
     * Scope a query to only include categories owned by a specific merchant or global categories.
     */
    public function scopeOwnedByMerchant(Builder $query, int $merchantId): void
    {
        $query->where(function ($q) use ($merchantId) {
            $q->where('merchant_id', $merchantId)
              ->orWhereNull('merchant_id');
        });
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Recursive children relationship
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
}