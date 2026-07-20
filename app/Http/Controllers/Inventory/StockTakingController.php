<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockTaking\StoreStockOpnameRequest;
use App\Http\Requests\Inventory\StockTaking\UpdateStockOpnameRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockOpname;
use App\Models\Outlet;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTakingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $opnames = StockOpname::currentBusiness()
            ->with(['outlet', 'items.inventoryItem'])
            ->filters($request->only(['search', 'status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $outlets = Outlet::currentBusiness()->active()->select('id', 'name')->get();

        $items = InventoryItem::currentBusiness()
            ->where('item_type', 'raw_material')
            ->with('uom')
            ->get()
            ->map(function ($item) {
                // Approximate current stock across all outlets to simplify form
                $item->current_stock = $item->balances()->sum('current_stock');

                return $item;
            });

        return inertia('Inventory/StockTaking/Index', [
            'opnames' => $opnames,
            'outlets' => $outlets,
            'items'   => $items,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(StoreStockOpnameRequest $request, StockOpnameService $service)
    {
        $service->createOpname($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Sesi Stock Opname berhasil disimpan.');
    }

    public function update(UpdateStockOpnameRequest $request, string $id, StockOpnameService $service)
    {
        $opname = StockOpname::currentBusiness()->findOrFail($id);
        $service->updateOpname($opname, $request->validated());

        return redirect()->back()->with('success', 'Hasil Stock Opname berhasil diupdate dan menunggu persetujuan.');
    }

    public function destroy(string $id)
    {
        $opname = StockOpname::currentBusiness()->findOrFail($id);

        if ($opname->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Hanya sesi In Progress yang dapat dibatalkan.');
        }

        $opname->delete();

        return redirect()->back()->with('success', 'Sesi Stock Opname berhasil dibatalkan.');
    }

    public function approve(UpdateStockOpnameRequest $request, string $id, StockOpnameService $service)
    {
        $opname = StockOpname::currentBusiness()->findOrFail($id);
        $service->completeOpname($opname, $request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Stock Opname berhasil disetujui, stok telah disesuaikan.');
    }
}
