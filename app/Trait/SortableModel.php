<?php

namespace App\Trait;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait SortableModel
{
    /**
     * Scope: sortable
     *
     * @param  Request|null  $request
     * @return Builder
     */
    public function scopeSortable(Builder $query, $sort, $direction = 'asc')
    {
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        $sortable = property_exists($this, 'sortable') ? $this->sortable : $this->getFillable();

        if ($sort && in_array($sort, $sortable)) {
            return $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
