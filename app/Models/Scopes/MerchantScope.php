<?php

namespace App\Models\Scopes;

use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class MerchantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::guard('merchant')->check()) {
            $builder->where($model->getTable() . 'merchant_id', Auth::user()->merchant_id);
        }
    }
}
