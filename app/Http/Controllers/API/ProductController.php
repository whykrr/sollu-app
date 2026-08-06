<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Master\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Search products for dropdowns / select2 API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query'     => 'nullable|string|min:3',
            'outlet_id' => 'nullable|uuid',
            'limit'     => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->query('limit', 20);

        $products = Product::currentBusiness()
            ->filters($request->only(['search', 'category', 'outlet']))
            ->when($request->query('query'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                          ->orWhereLike('code', "%{$search}%");
                });
            })
            ->when($request->query('outlet_id'), function ($q, $outletId) {
                $q->whereHas('outlets', function ($query) use ($outletId) {
                    $query->where('outlet_id', $outletId);
                });
            })
            ->take($limit)
            ->get();

        return $this->successResponse(ProductResource::collection($products));
    }
}
