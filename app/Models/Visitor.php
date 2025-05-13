<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip_address',
        'user_agent',
        'url',
        'referrer',
        'session_id',
        'created_month',
    ];

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn($builder, $value) => $builder->whereLike('url', "%$value%")
        )->when(
            $filters['from'] ?? false,
            fn($builder, $value) => $builder->whereBetween('created_at', [formatStartDate($value), formatEndDate($filters['to'])])
        )->when(
            $filters['month_from'] ?? false,
            fn($builder, $value) => $builder->whereBetween('created_month', [$value, $filters['month_to']])
        );
    }

    public function scopePerMonth(Builder $builder): Builder
    {
        return $builder->groupBy('MONTH(created_at)');
    }

    public function scopeUnique(Builder $builder): Builder
    {
        return $builder->groupBy('ip_address', 'session_id');
    }

    public function scopeFarthest(Builder $builder): Builder
    {
        return $builder->orderBy('created_at');
    }

    public function scopePerPage(Builder $builder): Builder
    {
        return $builder->groupBy('url');
    }
}
