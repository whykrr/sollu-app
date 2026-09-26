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
            'query' => 'nullable|string',
            'search' => 'nullable|string',
            'outlet_id' => 'nullable|uuid',
            'item_type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $businessId = $request->user()->business_id;
        $limit = (int) $request->query('limit', 20);
        $search = $request->query('query') ?: $request->query('search');

        $query = InventoryItem::query()
            ->where('inventory_items.business_id', $businessId)
            ->where('inventory_items.is_active', true)
            ->joinProductItem();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('inventory_items.name', "%{$search}%")
                    ->orWhereLike('product_items.sku', "%{$search}%")
                    ->orWhereLike('product_items.barcode', "%{$search}%");
            });
        }

        if ($request->query('item_type')) {
            $query->where('product_items.item_type', $request->query('item_type'));
        }

        if ($request->has('track_inventory')) {
            $query->where('product_items.track_inventory', $request->boolean('track_inventory'));
        }

        $items = $query
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
            'limit' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
        ]);

        $businessId = $request->user()->business_id;
        $limit = (int) $request->query('limit', 50);

        $query = InventoryItem::query()
            ->where('inventory_items.business_id', $businessId)
            ->where('inventory_items.is_active', true)
            ->joinProductItem()
            ->with(['uom', 'balances' => function ($q) use ($request) {
                if ($request->query('outlet_id')) {
                    $q->where('outlet_id', $request->query('outlet_id'));
                }
            }]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('inventory_items.name', "%{$search}%")
                    ->orWhereLike('product_items.sku', "%{$search}%")
                    ->orWhereLike('product_items.barcode', "%{$search}%");
            });
        }

        $items = $query->paginate($limit);

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
