<?php

namespace App\Models;

use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 *
 *
 * @property-read Collection|Business $business
 * @property-read Collection|User[] $users
 * @mixin IdeHelperOutlet
 */
class Outlet extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSlug;
    use HasBusiness;
    use SortableModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'is_main_outlet',
    ];

    protected $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the merchant that owns the Outlet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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

    /**
     * Get the subscription outlets
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptionOutlets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionOutlet::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }


}
