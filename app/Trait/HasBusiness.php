<?php

namespace App\Trait;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasBusiness
{
    /**
     * Scope: currentBusiness
     *
     * @return Builder
     */
    public function scopeCurrentBusiness(Builder $query)
    {
        return $query->where('business_id', Auth::user()->business_id);
    }

    /**
     * Scope: globalAndCurrentBusiness
     *
     * @return Builder
     */
    public function scopeGlobalAndCurrentBusiness(Builder $query)
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('business_id')
                ->orWhere('business_id', Auth::user()->business_id);
        });
    }
}
