<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Adjustment\StoreStockAdjustmentRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $adjustments = InventoryMovement::query()
            ->where('inventory_movements.business_id', $businessId)
            ->whereIn('movement_type', [
                InventoryMovementType::Adjustment,
                InventoryMovementType::Waste,
                InventoryMovementType::Opname,
            ])
            ->with(['inventoryItem', 'outlet', 'creator'])
            ->when($request->get('search'), function ($q, $search) {
                $q->whereHas('inventoryItem', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            })
            ->when($request->get('type'), function ($q, $type) {
                $q->where('movement_type', $type);
            })
            ->latest('inventory_movements.created_at')
            ->paginate(15)
            ->withQueryString();

        $outlets = Outlet::where('business_id', $businessId)
            ->active()
            ->select('id', 'name')
            ->get();

        $items = InventoryItem::currentBusiness()
            ->where('item_type', 'raw_material')
            ->with('uom')
            ->get()
            ->map(function ($item) {
                // Approximate current stock across all outlets to simplify form
                $item->current_stock = $item->balances()->sum('current_stock');

                return $item;
            });

        return inertia('Inventory/Adjustment/Index', [
            'adjustments' => $adjustments,
            'outlets'     => $outlets,
            'items'       => $items,
            'filters'     => $request->only(['search', 'type']),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, StockAdjustmentService $service)
    {
        $service->createAdjustment($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Penyesuaian stok berhasil disimpan.');
    }
}
