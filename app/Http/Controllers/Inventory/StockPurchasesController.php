<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Purchase\ReceivePurchaseOrderRequest;
use App\Http\Requests\Inventory\Purchase\StorePurchaseOrderRequest;
use App\Http\Requests\Inventory\Purchase\UpdatePurchaseOrderRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockPurchasesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $purchases = PurchaseOrder::currentBusiness()
            ->with(['supplier', 'outlet', 'items.inventoryItem'])
            ->filters($request->only(['search', 'status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::currentBusiness()->active()->select('id', 'name')->get();
        $outlets   = Outlet::currentBusiness()->active()->select('id', 'name')->get();
        $items     = InventoryItem::currentBusiness()->where('item_type', 'raw_material')->with('uom')->get();

        return inertia('Inventory/Purchase/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'outlets'   => $outlets,
            'items'     => $items,
            'filters'   => $request->only(['search', 'status']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service)
    {
        $service->createPO($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function update(UpdatePurchaseOrderRequest $request, string $id, PurchaseOrderService $service)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->updatePO($po, $request->validated());

        return redirect()->back()->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);

        if ($po->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya PO berstatus Draft yang dapat dihapus.');
        }

        $po->delete();

        return redirect()->back()->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function receive(ReceivePurchaseOrderRequest $request, string $id, PurchaseOrderService $service)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->receivePO($po, $request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Penerimaan barang berhasil dicatat.');
    }
}
