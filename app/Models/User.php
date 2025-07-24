<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmailMerchant;
use App\Traits\MerchantOwned;
use DateTimeInterface;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
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
    use MerchantOwned;

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
}
