<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryBalance;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     * Shows current stock position per item, per outlet.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $outletId   = $request->get('outlet_id');

        $stockQuery = InventoryBalance::query()
            ->where('inventory_balances.business_id', $businessId)
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->join('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->where('inventory_items.is_active', true)
            ->select([
                'inventory_balances.id',
                'inventory_balances.outlet_id',
                'inventory_balances.inventory_item_id',
                'inventory_balances.current_stock',
                'inventory_items.name as item_name',
                'inventory_items.sku',
                'inventory_items.minimum_stock',
                'uoms.name as uom',
                'outlets.name as outlet_name',
            ]);

        if ($outletId) {
            $stockQuery->where('inventory_balances.outlet_id', $outletId);
        }

        if ($request->get('search')) {
            $search = $request->get('search');
            $stockQuery->where(function ($q) use ($search) {
                $q->where('inventory_items.name', 'like', "%{$search}%")
                    ->orWhere('inventory_items.sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $stockQuery->whereRaw('inventory_balances.current_stock < inventory_items.minimum_stock');
        }

        if ($request->boolean('out_of_stock')) {
            $stockQuery->where('inventory_balances.current_stock', '<=', 0);
        }

        $stocks = $stockQuery
            ->selectRaw('inventory_balances.current_stock < inventory_items.minimum_stock as is_low_stock')
            ->orderBy('inventory_items.name')
            ->paginate(15)
            ->withQueryString();

        $outlets = Outlet::where('business_id', $businessId)
            ->active()
            ->select('id', 'name')
            ->get();

        return inertia('Inventory/Stock/Index', [
            'stocks'  => $stocks,
            'outlets' => $outlets,
            'filters' => $request->only(['search', 'low_stock', 'out_of_stock', 'outlet_id']),
        ]);
    }
}
