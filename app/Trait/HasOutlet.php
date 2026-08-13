<?php

namespace App\Trait;

use App\Helpers\SelectedOutlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasOutlet
{
    /**
     * Scope: selectedOutlet
     *
     * @param  string|int  $filter
     * @return Builder
     */
    public function scopeSelectedOutlet(Builder $query, $filter = null)
    {
        $selectedOutlet = SelectedOutlet::make()->get();
        $userOutlets = Auth::user()->outlets()->pluck('id');

        if ($selectedOutlet !== null) {
            return $query->whereHas('outlets', fn (Builder $q) => $q->where('outlets.id', $selectedOutlet->id));
        } elseif ($filter) {
            return $query->whereHas('outlets', fn (Builder $q) => $q->where('outlets.id', $filter));
        } else {
            return $query->whereHas('outlets', fn (Builder $q) => $q->whereIn('outlets.id', $userOutlets));
        }
    }
}
