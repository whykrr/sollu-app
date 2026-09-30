<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Master\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Search product categories for async select/picker.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('query') ?: $request->input('search');

        $categories = ProductCategory::currentBusiness()
            ->when($search, function ($query, $search) {
                $query->whereLike('name', "%{$search}%");
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get(['id', 'name'])
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'value' => $cat->id,
                    'name' => $cat->name,
                ];
            });

        return response()->json($categories);
    }
}
