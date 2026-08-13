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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read Collection|Business $business
 * @property-read Collection|User[] $users
 *
 * @mixin IdeHelperOutlet
 */
class Outlet extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasSlug;
    use HasUuids;
    use SoftDeletes;
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
        'phone',
        'email',
        'timezone',
        'currency_code',
        'logo_url',
        'is_stock_frozen',
    ];

    protected $sortable = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_main_outlet' => 'boolean',
            'is_stock_frozen' => 'boolean',
        ];
    }

    /**
     * Get the merchant that owns the Outlet
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The users that belong to the Outlet
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_user', 'outlet_id', 'user_id');
    }

    /**
     * Get the subscription outlets
     */
    public function subscriptionOutlets(): HasMany
    {
        return $this->hasMany(SubscriptionOutlet::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(OutletSetting::class);
    }

    public function operationalHours(): HasMany
    {
        return $this->hasMany(OutletOperationalHour::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(OutletDevice::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(OutletAuditLog::class);
    }

    public function scopeActive($query, bool $active = true)
    {
        return $query->where('is_active', $active);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
