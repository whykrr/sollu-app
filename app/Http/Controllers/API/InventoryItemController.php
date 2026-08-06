<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\Inventory\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    /**
     * Search inventory items for dropdowns / select2 API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query'     => 'nullable|string|min:3',
            'outlet_id' => 'nullable|uuid',
            'limit'     => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->query('limit', 20);

        $items = InventoryItem::currentBusiness()
            ->where('is_active', true)
            ->filters($request->only(['search', 'item_type', 'track_inventory']))
            ->when($request->query('query'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->with(['uom', 'balances' => function ($q) use ($request) {
                if ($request->query('outlet_id')) {
                    $q->where('outlet_id', $request->query('outlet_id'));
                }
            }])
            ->take($limit)
            ->get()
            ->map(function ($item) use ($request) {
                if ($request->query('outlet_id')) {
                    $item->current_stock = $item->balances->first()?->current_stock_formatted ?? 0;
                }

                return $item;
            });

        return $this->successResponse(InventoryItemResource::collection($items));
    }

    /**
     * Get paginated active inventory items for partial loading (e.g. stock opname).
     */
    public function getPartialItems(Request $request)
    {
        $request->validate([
            'outlet_id' => 'nullable|uuid',
            'limit'     => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->query('limit', 50);

        $items = InventoryItem::currentBusiness()
            ->where('is_active', true)
            ->with(['uom', 'balances' => function ($q) use ($request) {
                if ($request->query('outlet_id')) {
                    $q->where('outlet_id', $request->query('outlet_id'));
                }
            }])
            ->paginate($limit);

        // Map the items to inject formatted stock
        $items->getCollection()->transform(function ($item) use ($request) {
            if ($request->query('outlet_id')) {
                $item->current_stock = $item->balances->first()?->current_stock_formatted ?? 0;
            }
            return $item;
        });

        return InventoryItemResource::collection($items);
    }
}
