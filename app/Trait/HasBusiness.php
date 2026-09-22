<?php

namespace App\Trait;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasBusiness
{
    /**
     * Scope: currentBusiness
     */
    public function scopeCurrentBusiness(Builder $query, ?string $businessId = null): Builder
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        return $query->where($this->qualifyColumn('business_id'), $businessId);
    }

    /**
     * Scope: globalAndCurrentBusiness
     */
    public function scopeGlobalAndCurrentBusiness(Builder $query, ?string $businessId = null): Builder
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        return $query->where(function (Builder $query) use ($businessId) {
            $query->whereNull($this->qualifyColumn('business_id'))
                ->orWhere($this->qualifyColumn('business_id'), $businessId);
        });
    }
}
