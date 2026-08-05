<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Purchase\ReceivePurchaseOrderRequest;
use App\Http\Requests\Inventory\Purchase\StorePurchaseOrderRequest;
use App\Http\Requests\Inventory\Purchase\UpdatePurchaseOrderRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use App\Services\Inventory\PurchaseOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockPurchasesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, 'Anda tidak memiliki akses.');

        $purchases = PurchaseOrder::currentBusiness()
            ->with(['supplier:id,name', 'outlet:id,name'])
            ->filters($request->only(['search', 'status', 'supplier_id', 'outlet_id', 'start_date', 'end_date']))
            ->when($request->get('sort'), function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->get('direction', 'asc'));
            }, function ($query) {
                $query->latest();
            })
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $suppliers = Supplier::currentBusiness()->active()->select('id', 'name')->get();
        $outlets   = Outlet::currentBusiness()->active()->select('id', 'name')->get();
        $uoms      = Uom::select('id', 'name')->get();

        return inertia('Inventory/Purchase/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'outlets'   => $outlets,
            'uoms'      => $uoms,
            'filters'   => $request->only(['search', 'status', 'supplier_id', 'outlet_id', 'start_date', 'end_date']),
        ]);
    }

    public function show(Request $request, string $id)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, 'Anda tidak memiliki akses.');

        $purchase = PurchaseOrder::currentBusiness()
            ->with(['supplier', 'outlet', 'items.inventoryItem.uom', 'items.uom'])
            ->findOrFail($id);

        return response()->json($purchase);
    }

    public function searchItems(Request $request)
    {
        $search     = $request->get('search');
        $supplierId = $request->get('supplier_id');

        $items = InventoryItem::currentBusiness()
            ->with('uom:id,name')
            ->when($search, function ($query, $search) {
                $query->whereLike('name', "%{$search}%");
            })
            ->when($supplierId, function ($query, $supplierId) {
                $query->withExists(['suppliers as is_supplied' => function ($q) use ($supplierId) {
                    $q->where('suppliers.id', $supplierId);
                }]);
            })
            ->limit(50)
            ->get(['id', 'name', 'uom_id', 'sku']);

        return response()->json($items);
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service)
    {
        $service->createPO($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function update(UpdatePurchaseOrderRequest $request, string $id, PurchaseOrderService $service)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->updatePO($po, $request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function order(string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_UPDATE->value), 403, 'Anda tidak memiliki akses.');

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->markAsOrdered($po, Auth::user());

        return redirect()->back()->with('success', 'Purchase Order berhasil ditandai sebagai Ordered.');
    }

    public function cancel(string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_CANCEL->value), 403, 'Anda tidak memiliki akses.');

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->cancel($po, Auth::user());

        return redirect()->back()->with('success', 'Purchase Order berhasil dibatalkan.');
    }

    public function destroy(string $id)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_UPDATE->value), 403, 'Anda tidak memiliki akses.');

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
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

    public function void(string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_CANCEL->value), 403, 'Anda tidak memiliki akses.');

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->void($po, Auth::user());

        return redirect()->back()->with('success', 'Penerimaan barang berhasil dibatalkan (void).');
    }

    /**
     * Download PDF
     */
    public function pdf(Request $request, string $id)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, 'Anda tidak memiliki akses.');

        $po = PurchaseOrder::currentBusiness()
            ->with(['supplier', 'outlet', 'items.inventoryItem.uom', 'items.uom'])
            ->findOrFail($id);

        $business = $request->user()->business;

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'po'       => $po,
            'business' => $business,
        ]);

        return $pdf->download('PO-' . $po->po_number . '.pdf');
    }
}
