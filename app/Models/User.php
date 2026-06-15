<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmailBusiness;
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
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read Collection|Business $business
 * @property-read Collection|Outlet[] $outlets
 * @mixin HasRoles
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasUuids;
    use SoftDeletes;
    use CanResetPassword;
    use SortableModel;
    use HasBusiness;
    use HasOutlet;

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

    protected $sortable = [
        'name',
        'email',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'     => 'hashed',
            'pin'          => 'hashed',
            'is_root_user' => 'boolean',
        ];
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailBusiness());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the business that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The outlets that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
                $q->whereLike('name', "%{$value}%")->orWhereLike('email', "%{$value}%");
            })
        )->when(
            $filters['role'] ?? false,
            fn (Builder $builder, $value) => $builder->whereHas('roles', function (Builder $q) use ($value) {
                $q->where('roles.name', $value);
            })
        )->when(
            $filters['is_deleted'] ?? false,
            fn (Builder $builder, $value) => $builder->withTrashed()
        );
    }
}
