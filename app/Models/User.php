<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmailMerchant;
use App\Trait\SortableModel;
use DateTimeInterface;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
 * @property-read Collection|Merchant $merchant
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
        'password',
        'pin',
        'photo',
        'is_root_user',
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
            'password'     => 'hashed',
            'pin'          => 'hashed',
            'is_root_user' => 'boolean',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }


    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailMerchant());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the merchant that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Merchant
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
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
            $filters['outlet'] ?? false,
            fn ($builder, $value) => $builder->whereHas('outlets', function (Builder $q) use ($value) {
                $q->where('outlets.id', $value);
            })
        )->when(
            $filters['role'] ?? false,
            fn (EloquentBuilder $builder, $value) => $builder->whereHas('roles', function (Builder $q) use ($value) {
                $q->where('roles.name', $value);
            })
        )->when(
            $filters['status'] ?? false,
            fn (EloquentBuilder $builder, $value) => ($value == 'archived')
            ? $builder->onlyTrashed()
            : $builder->withTrashed()
        );
    }
}
