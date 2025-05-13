<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use DateTimeInterface;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $sortable = [
        'name',
        'role',
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
            'password' => 'hashed',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }

    public function message_response(): HasMany|MessageResponse
    {
        return $this->hasMany(MessageResponse::class);
    }
    public function scopeFilter(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when(
                $filters['search'] ?? false,
                fn($builder, $value) =>
                $builder->where(function ($nestedBuilder) use ($value) {
                    $nestedBuilder->where('name', 'like', "%$value%")
                        ->orWhere('email', 'like', "%$value%");
                })
            )
            ->when(
                $filters['by'] ?? false,
                fn($builder, $value) =>
                !in_array($value, $this->sortable)
                    ? $builder
                    : $builder->orderBy($value, $filters['order'] ?? 'desc'),
                fn($builder) => $builder->orderBy('created_at', 'desc')
            )
            ->when(
                $filters['status'] ?? false,
                fn($builder, $value) => ($value != 'deleted')
                    ? $builder
                    : $builder->onlyTrashed()
            );
    }

    public function scopeClient(Builder $builder): Builder
    {
        return $builder->where('role', '!=', 'superadmin');
    }
}
