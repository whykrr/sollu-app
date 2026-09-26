<?php

namespace App\Http\Controllers\App\Inventory;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\DatePresetEnum;
use App\Enums\PermissionEnum;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\Purchase\DirectPurchaseRequest;
use App\Http\Requests\App\Inventory\Purchase\GetPurchaseOrderRequest;
use App\Http\Requests\App\Inventory\Purchase\ReceivePurchaseOrderRequest;
use App\Http\Requests\App\Inventory\Purchase\StorePurchaseOrderRequest;
use App\Http\Requests\App\Inventory\Purchase\StorePurchaseReturnRequest;
use App\Http\Requests\App\Inventory\Purchase\UpdatePurchaseOrderRequest;
use App\Jobs\Inventory\ExportPurchaseOrderJob;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\PurchaseReturn;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use App\Services\App\Inventory\GoodsReceiptService;
use App\Services\App\Inventory\PurchaseOrderService;
use App\Services\App\Inventory\PurchaseReturnService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StockPurchasesController extends Controller
{
    /**
     * Display a listing of the purchase orders.
     */
    public function index(GetPurchaseOrderRequest $request)
    {
        $filters = $request->validated();
        $filters['outlet_id'] = SelectedOutlet::resolveEffectiveOutletId($request->user(), $request->validated('outlet_id') ?: $request->validated('outlet'));

        $dateRange = DatePresetEnum::resolveRange(
            $request->validated('preset', DatePresetEnum::THIS_MONTH->value),
            $request->validated('start_date'),
            $request->validated('end_date')
        );
        $filters['start_date'] = $dateRange['start_date'];
        $filters['end_date'] = $dateRange['end_date'];
        $filters['preset'] = $dateRange['preset'];

        $purchases = PurchaseOrder::currentBusiness()
            ->with(['supplier:id,name', 'outlet:id,name'])
            ->filters($filters)
            ->sortable($request->validated('sort', 'created_at'), $request->validated('direction', 'desc'))
            ->paginate($request->validated('perpage', 20))
            ->withQueryString();

        $suppliers = Supplier::currentBusiness()->active()->select('id', 'name')->get();
        $outlets = Outlet::currentBusiness()->where('is_active', true)->select('id', 'name')->get();
        $uoms = Uom::select('id', 'name', 'code')->orderBy('name')->get();

        return inertia('Inventory/Purchase/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'outlets' => $outlets,
            'uoms' => $uoms,
            'params' => $filters,
        ]);
    }

    /**
     * Display the specified purchase order detail (On-Demand Loading for PopUp Drawer).
     */
    public function show(Request $request, string $id)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $purchase = PurchaseOrder::currentBusiness()
            ->with([
                'supplier',
                'outlet',
                'creator:id,name',
                'approver:id,name',
                'items.inventoryItem:id,product_item_id,name,uom_id',
                'items.inventoryItem.productItem:id,sku,barcode',
                'items.inventoryItem.uom:id,name,code',
                'items.uom:id,name,code',
                'goodsReceipts.items.uom:id,name,code',
                'goodsReceipts.items.inventoryItem:id,product_item_id,name,uom_id',
                'goodsReceipts.items.inventoryItem.productItem:id,sku,barcode',
                'goodsReceipts.items.inventoryItem.uom:id,name,code',
                'goodsReceipts.items.purchaseOrderItem',
                'goodsReceipts.items.purchaseReturnItems.purchaseReturn:id,status',
                'goodsReceipts.receiver:id,name',
                'purchaseReturns.items.uom:id,name,code',
                'purchaseReturns.items.inventoryItem:id,product_item_id,name',
                'purchaseReturns.items.inventoryItem.productItem:id,sku',
                'purchaseReturns.creator:id,name',
            ])
            ->findOrFail($id);

        return response()->json($purchase);
    }

    /**
     * Search inventory items for purchase order with outlet and supplier awareness.
     */
    public function searchItems(Request $request)
    {
        $search = $request->get('query') ?: $request->get('search');
        $supplierId = $request->get('supplier_id');
        $outletId = $request->get('outlet_id');

        $items = InventoryItem::query()
            ->where('inventory_items.business_id', Auth::user()->business_id)
            ->where('inventory_items.is_active', true)
            ->joinProductItem()
            ->with(['uom:id,name,code', 'balances' => function ($q) use ($outletId) {
                if ($outletId) {
                    $q->where('outlet_id', $outletId);
                }
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('inventory_items.name', "%{$search}%")
                        ->orWhereLike('product_items.sku', "%{$search}%")
                        ->orWhereLike('product_items.barcode', "%{$search}%");
                });
            })
            ->when($supplierId, function ($query, $supplierId) {
                $query->withExists(['suppliers as is_supplied' => function ($q) use ($supplierId) {
                    $q->where('suppliers.id', $supplierId);
                }]);
            })
            ->limit(30)
            ->get()
            ->map(function ($item) use ($outletId) {
                $item->current_stock = $outletId ? ($item->balances->first()?->current_stock ?? 0) : 0;

                return $item;
            });

        return response()->json($items);
    }

    /**
     * Store a newly created purchase order (Draft).
     */
    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service)
    {
        $service->createPO($request->validated(), Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Store a direct purchase (Instant Goods Receipt & Inventory In).
     */
    public function directStore(DirectPurchaseRequest $request, PurchaseOrderService $service)
    {
        $service->directPurchase($request->validated(), Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pembelian langsung berhasil disimpan dan stok telah masuk.'
        );
    }

    /**
     * Update the specified purchase order (Draft).
     */
    public function update(UpdatePurchaseOrderRequest $request, string $id, PurchaseOrderService $service)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->updatePO($po, $request->validated(), Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    /**
     * Mark the purchase order as ordered.
     */
    public function order(string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_UPDATE->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->markAsOrdered($po, Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Purchase Order berhasil diproses menjadi pesanan.'
        );
    }

    /**
     * Cancel an ordered purchase order.
     */
    public function cancel(string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_CANCEL->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->cancel($po, Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Purchase Order berhasil dibatalkan.'
        );
    }

    /**
     * Remove the specified draft purchase order.
     */
    public function destroy(string $id)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_UPDATE->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);

        if ($po->status !== PurchaseOrderStatus::Draft) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Hanya pesanan berstatus draf yang dapat dihapus.'
            );
        }

        $po->delete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    /**
     * Receive items for the purchase order (Full or Partial delivery).
     */
    public function receive(ReceivePurchaseOrderRequest $request, string $id, GoodsReceiptService $service)
    {
        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->createReceipt($po, $request->validated(), Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Penerimaan barang berhasil dicatat.'
        );
    }

    /**
     * Void an entire purchase order and reverse all its stock receipts.
     */
    public function void(Request $request, string $id, PurchaseOrderService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_VOID->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $po = PurchaseOrder::currentBusiness()->findOrFail($id);
        $service->void($po, Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pembelian berhasil dibatalkan (void) dan seluruh stok telah disesuaikan.'
        );
    }

    /**
     * Void a specific goods receipt document and reverse its stock.
     */
    public function voidReceipt(Request $request, string $receiptId, GoodsReceiptService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_VOID->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $receipt = GoodsReceipt::currentBusiness()->findOrFail($receiptId);

        try {
            $service->voidReceipt($receipt, Auth::user(), $request->input('reason'));
        } catch (HttpException $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Penerimaan barang berhasil dibatalkan dan stok telah disesuaikan.'
        );
    }

    /**
     * Store a purchase return to supplier and deduct inventory.
     */
    public function returnStore(StorePurchaseReturnRequest $request, PurchaseReturnService $service)
    {
        $service->createReturn($request->validated(), Auth::user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Retur pembelian berhasil dicatat dan stok telah dikurangi.'
        );
    }

    /**
     * Void a specific purchase return and restore inventory.
     */
    public function voidReturn(Request $request, string $returnId, PurchaseReturnService $service)
    {
        abort_if(! Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_VOID->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $return = PurchaseReturn::currentBusiness()->findOrFail($returnId);

        try {
            $service->voidReturn($return, Auth::user(), $request->input('reason'));
        } catch (HttpException $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Retur pembelian berhasil dibatalkan dan stok telah dikembalikan.'
        );
    }

    /**
     * Download PDF format for the purchase order.
     */
    public function pdf(Request $request, string $id)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $po = PurchaseOrder::currentBusiness()
            ->with(['supplier', 'outlet', 'items.inventoryItem.uom', 'items.uom'])
            ->findOrFail($id);

        $business = $request->user()->business;

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'po' => $po,
            'business' => $business,
        ]);

        return $pdf->download('PO-'.$po->po_number.'.pdf');
    }

    /**
     * Download PDF format for the goods receipt slip.
     */
    public function receiptPdf(Request $request, string $receiptId)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $receipt = GoodsReceipt::currentBusiness()
            ->with(['purchaseOrder.supplier', 'outlet', 'receiver', 'items.inventoryItem.uom', 'items.uom'])
            ->findOrFail($receiptId);

        $business = $request->user()->business;

        $pdf = Pdf::loadView('pdf.goods-receipt', [
            'receipt' => $receipt,
            'business' => $business,
        ]);

        return $pdf->download('GR-'.$receipt->receipt_number.'.pdf');
    }

    /**
     * Download PDF format for the purchase return slip.
     */
    public function returnPdf(Request $request, string $returnId)
    {
        abort_if(! $request->user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value), 403, AuthorizationMessage::CANT_ACCESS_PAGE);

        $return = PurchaseReturn::currentBusiness()
            ->with(['supplier', 'outlet', 'creator', 'items.inventoryItem.uom', 'items.uom'])
            ->findOrFail($returnId);

        $business = $request->user()->business;

        $pdf = Pdf::loadView('pdf.purchase-return', [
            'return' => $return,
            'business' => $business,
        ]);

        return $pdf->download('PR-'.$return->return_number.'.pdf');
    }

    /**
     * Trigger asynchronous CSV export for purchase orders.
     */
    public function exportCsv(GetPurchaseOrderRequest $request)
    {
        ExportPurchaseOrderJob::dispatch(
            Auth::user(),
            Auth::user()->business_id,
            $request->validated()
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor pembelian (CSV) sedang diproses di latar belakang. Notifikasi akan masuk jika sudah selesai.'
        );
    }
}
