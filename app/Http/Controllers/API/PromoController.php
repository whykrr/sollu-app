<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoResource;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Search active promos for dropdown / select2 API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string',
            'search' => 'nullable|string',
            'outlet_id' => 'nullable|uuid',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->query('limit', 20);
        $outletId = $request->query('outlet_id');
        $searchTerm = $request->query('query') ?: $request->query('search');

        $promos = Promo::currentBusiness()
            ->active()
            ->when($outletId, function ($q, $outletId) {
                $q->where(function ($sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn ($o) => $o->where('outlet_id', $outletId));
                });
            })
            ->when($searchTerm, function ($q, $search) {
                $q->whereLike('name', "%{$search}%");
            })
            ->with('products:id')
            ->take($limit)
            ->get();

        return $this->successResponse(PromoResource::collection($promos));
    }
}
