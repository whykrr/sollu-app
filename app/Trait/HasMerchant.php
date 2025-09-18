<?php

namespace App\Trait;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasMerchant
{
    /**
     * Scope: currentMerchant
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCurrentMerchant(Builder $query)
    {
        return $query->where('merchant_id', Auth::user()->merchant_id);
    }

    /**
    * Scope: globalAndCurrentMerchant
    *
     * @param Builder $query
     * @return Builder
    */
    public function scopeGlobalAndCurrentMerchant(Builder $query)
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('merchant_id')
                ->orWhere('merchant_id', Auth::user()->merchant_id);
        });
    }
}
