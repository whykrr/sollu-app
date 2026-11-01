<?php

namespace App\Models;

use App\Models\Permission;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmailBusiness;
use App\Services\Auth\UserPermissionCacheService;
use App\Trait\HasBusiness;
use App\Trait\HasOutlet;
use App\Trait\SortableModel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read Collection|Business $business
 * @property-read Collection|Outlet[] $outlets
 *
 * @mixin HasRoles
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use CanResetPassword;
    use HasBusiness;
    use HasFactory;
    use HasOutlet;
    use HasRoles {
        hasPermissionTo as spatieHasPermissionTo;
        hasAnyPermission as spatieHasAnyPermission;
        hasAllPermissions as spatieHasAllPermissions;
        hasDirectPermission as spatieHasDirectPermission;
        getAllPermissions as spatieGetAllPermissions;
    }
    use HasUuids;
    use Notifiable;
    use SoftDeletes;
    use SortableModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'password',
        'pin',
        'photo',
        'is_root_user',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'has_pin',
        'has_web_access',
        'photo_url',
    ];

    protected $sortable = [
        'name',
        'email',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pin' => 'hashed',
            'is_root_user' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user has a PIN set.
     */
    public function getHasPinAttribute(): bool
    {
        return ! empty($this->pin);
    }

    /**
     * Determine if the user has web dashboard access.
     */
    public function getHasWebAccessAttribute(): bool
    {
        return ! empty($this->password);
    }

    /**
     * Get the photo URL for the user.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? Storage::url($this->photo)
            : null;
    }

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailBusiness);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the business that owns the User
     *
     * @return BelongsTo|Business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The outlets that belong to the User
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'outlet_user', 'user_id', 'outlet_id');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn ($builder, $value) => $builder->where(function ($q) use ($value) {
                $q->whereLike('name', "%{$value}%")
                    ->orWhereLike('email', "%{$value}%")
                    ->orWhereLike('phone', "%{$value}%");
            })
        )->when(
            $filters['role'] ?? false,
            fn (Builder $builder, $value) => $builder->whereHas('roles', function (Builder $q) use ($value) {
                $q->where('roles.name', $value);
            })
        )->when(
            $filters['outlet'] ?? null,
            fn (Builder $builder, $value) => $builder->whereHas('outlets', function (Builder $q) use ($value) {
                $q->where('outlets.id', $value);
            })
        )->when(
            $filters['is_deleted'] ?? false,
            fn (Builder $builder, $value) => $builder->onlyTrashed()
        );
    }

    /**
     * Determine if the model may perform the given permission.
     * Root user has wildcard '*' permission and bypasses all checks.
     *
     * @param  string|int|\Spatie\Permission\Contracts\Permission|\BackedEnum  $permission
     * @param  string|null  $guardName
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->is_root_user) {
            return true;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    /**
     * Determine if the entity has the given abilities.
     * Root user passes all abilities automatically.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     */
    public function can($abilities, $arguments = []): bool
    {
        if ($this->is_root_user) {
            return true;
        }

        return parent::can($abilities, $arguments);
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param  string|int|array|\Spatie\Permission\Contracts\Permission|\Illuminate\Support\Collection|\BackedEnum  ...$permissions
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if ($this->is_root_user) {
            return true;
        }

        return $this->spatieHasAnyPermission(...$permissions);
    }

    /**
     * Determine if the model has all of the given permissions.
     *
     * @param  string|int|array|\Spatie\Permission\Contracts\Permission|\Illuminate\Support\Collection|\BackedEnum  ...$permissions
     */
    public function hasAllPermissions(...$permissions): bool
    {
        if ($this->is_root_user) {
            return true;
        }

        return $this->spatieHasAllPermissions(...$permissions);
    }

    /**
     * Determine if the model has the given direct permission.
     *
     * @param  string|int|\Spatie\Permission\Contracts\Permission|\BackedEnum  $permission
     */
    public function hasDirectPermission($permission): bool
    {
        if ($this->is_root_user) {
            return true;
        }

        return $this->spatieHasDirectPermission($permission);
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     * For root users, return all available permissions so collection methods like
     * contains() or pluck() work transparently.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        if ($this->is_root_user) {
            /** @var class-string<Permission> $permissionClass */
            $permissionClass = config('permission.models.permission', Permission::class);

            return $permissionClass::all();
        }

        $permissions = $this->spatieGetAllPermissions();

        return $permissions instanceof Collection
            ? $permissions
            : new Collection($permissions);
    }

    /**
     * Get all permissions for this user from cache.
     *
     * @return array<int, string>
     */
    public function getCachedPermissions(?string $businessId = null): array
    {
        return app(UserPermissionCacheService::class)
            ->getPermissions($this, $businessId);
    }

    /**
     * Determine if the user has the given permission using cache.
     */
    public function hasCachedPermission(string $permission, ?string $businessId = null): bool
    {
        return app(UserPermissionCacheService::class)
            ->hasPermission($this, $permission, $businessId);
    }

    /**
     * Invalidate cached permissions for this user.
     */
    public function clearCachedPermissions(?string $businessId = null): void
    {
        app(UserPermissionCacheService::class)
            ->clearUserPermissions($this, $businessId);
    }
}
