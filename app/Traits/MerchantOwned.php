<?php

namespace App\Traits;

use App\Models\Scopes\MerchantScope;
use Auth;

trait MerchantOwned
{
    /**
     * Boot the trait.
     */
    public static function bootMerchantOwned()
    {
        static::addGlobalScope(new MerchantScope());
    }

    /**
     * Remove the merchant scope from the query.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withoutMerchantScope()
    {
        return static::withoutGlobalScope(MerchantScope::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function withGlobal()
    {
        return static::withoutMerchantScope()->where(function ($q) {
            $merchantId = Auth::user()->merchant_id ?? null;
            $q->where('merchant_id', $merchantId)
              ->orWhereNull('merchant_id');
        });
    }
}
