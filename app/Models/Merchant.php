<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Merchant extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'logo_url',
        'already_free_trial',
        'merchant_type_id',
        'settings',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'already_free_trial' => 'boolean',
        'settings'           => 'json',
    ];

    /**
     * Get the industry that owns the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MerchantType::class);
    }

    /**
     * Get all of the outlets for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /**
     * Get all of the users for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all of the subscriptions for the Merchant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantOutletSubscription::class);
    }

    /**
     * @inheritDoc
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
