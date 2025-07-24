<?php

namespace App\Models;

use App\Traits\MerchantOwned;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @mixin IdeHelperOutlet
 */
class Outlet extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSlug;
    use MerchantOwned;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'status',
        'expired_at',
        'is_main_outlet',
    ];

    /**
     * Get the merchant that owns the Outlet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get all of the subscription_plans for the Outlet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription_plans(): HasMany
    {
        return $this->hasMany(MerchantOutletSubscription::class);
    }

    /**
     * The users that belong to the Outlet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_user', 'user_id', 'outlet_id');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }


}
