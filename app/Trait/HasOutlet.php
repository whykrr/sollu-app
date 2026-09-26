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
     * @param  string|int|null  $filter
     * @return Builder
     */
    public function scopeSelectedOutlet(Builder $query, $filter = null)
    {
        $user = Auth::user();
        if (! $user) {
            return $query;
        }

        $effectiveOutletId = SelectedOutlet::resolveEffectiveOutletId($user, $filter ? (string) $filter : null);

        if ($effectiveOutletId !== null) {
            return $query->whereHas('outlets', fn (Builder $q) => $q->where('outlets.id', $effectiveOutletId));
        }

        $accessibleList = SelectedOutlet::make($user)->getAccessibleOutletsList();
        $userOutlets = array_map(function ($item) {
            return is_array($item) ? ($item['id'] ?? null) : $item->id;
        }, $accessibleList);
        $userOutlets = array_values(array_filter($userOutlets));

        return $query->whereHas('outlets', fn (Builder $q) => $q->whereIn('outlets.id', $userOutlets));
    }
}
