<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $movements = InventoryMovement::query()
            ->where('inventory_movements.business_id', $businessId)
            ->with(['inventoryItem.uom', 'outlet', 'creator'])
            ->when($request->get('search'), function ($q, $search) {
                $q->whereHas('inventoryItem', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            })
            ->when($request->get('movement_type'), function ($q, $type) {
                $q->where('movement_type', $type);
            })
            ->when($request->get('outlet_id'), function ($q, $outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->when($request->get('date_from'), function ($q, $date) {
                $q->whereDate('inventory_movements.created_at', '>=', $date);
            })
            ->when($request->get('date_to'), function ($q, $date) {
                $q->whereDate('inventory_movements.created_at', '<=', $date);
            })
            ->latest('inventory_movements.created_at')
            ->paginate(25)
            ->withQueryString();

        $outlets = Outlet::where('business_id', $businessId)
            ->active()
            ->select('id', 'name')
            ->get();

        $items = InventoryItem::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        return inertia('Inventory/Movement/Index', [
            'movements' => $movements,
            'outlets' => $outlets,
            'items' => $items,
            'filters' => $request->only(['search', 'movement_type', 'outlet_id', 'date_from', 'date_to']),
        ]);
    }
}
