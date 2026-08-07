<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Transfer\ProcessStockTransferRequest;
use App\Http\Requests\Inventory\Transfer\StoreStockTransferRequest;
use App\Http\Requests\Inventory\Transfer\UpdateStockTransferRequest;
use App\Models\Inventory\StockTransfer;
use App\Services\Inventory\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $stockTransferService
    ) {}

    public function index(Request $request)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_READ->value);

        $query = StockTransfer::query()
            ->with(['fromOutlet:id,name', 'toOutlet:id,name', 'requester:id,name'])
            ->withCount('items');

        if ($request->filled('search')) {
            $query->where('transfer_number', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_outlet_id')) {
            $query->where('from_outlet_id', $request->from_outlet_id);
        }

        if ($request->filled('to_outlet_id')) {
            $query->where('to_outlet_id', $request->to_outlet_id);
        }

        if ($request->filled('sort')) {
            $direction = $request->get('direction', 'asc');
            $query->orderBy($request->sort, $direction);
        } else {
            $query->latest();
        }

        $transfers = $query->paginate($request->get('per_page', 10))->withQueryString();
        $outlets = \App\Models\Outlet::currentBusiness()->where('is_active', true)->select('id', 'name', 'is_stock_frozen')->get();

        return inertia('Inventory/Transfer/Index', [
            'transfers' => $transfers,
            'outlets'   => $outlets,
            'filters'   => $request->only(['search', 'status', 'from_outlet_id', 'to_outlet_id', 'sort', 'direction']),
        ]);
    }

    public function store(StoreStockTransferRequest $request)
    {
        $this->stockTransferService->createTransfer($request->validated(), $request->user());

        return redirect()->back()->with('success', 'Permintaan transfer stok berhasil dibuat.');
    }

    public function show(StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_READ->value);

        $transfer->load([
            'fromOutlet:id,name,is_stock_frozen', 
            'toOutlet:id,name,is_stock_frozen', 
            'requester:id,name', 
            'approver:id,name', 
            'receiver:id,name',
            'items.inventoryItem.uom:id,name,code'
        ]);

        return response()->json([
            'data' => $transfer
        ]);
    }

    public function exportPdf(StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_READ->value);

        $transfer->load([
            'fromOutlet', 
            'toOutlet', 
            'requester', 
            'approver', 
            'receiver',
            'items.inventoryItem.uom'
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inventory.transfer-detail', [
            'data'     => $transfer,
            'business' => auth()->user()->business,
            'outlet'   => $transfer->fromOutlet,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Transfer_Stok_' . $transfer->transfer_number . '.pdf');
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $transfer)
    {
        $this->stockTransferService->updateTransfer($transfer, $request->validated());

        return redirect()->back()->with('success', 'Transfer stok berhasil diperbarui.');
    }

    public function approve(Request $request, StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_APPROVE->value);

        $this->stockTransferService->approveTransfer($transfer, $request->user());

        return redirect()->back()->with('success', 'Transfer stok disetujui.');
    }

    public function reject(Request $request, StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_APPROVE->value);
        
        $request->validate(['notes' => 'required|string']);

        $this->stockTransferService->rejectTransfer($transfer, $request->only('notes'), $request->user());

        return redirect()->back()->with('success', 'Transfer stok ditolak.');
    }

    public function ship(Request $request, StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_SHIP->value);

        $this->stockTransferService->shipTransfer($transfer, $request->user());

        return redirect()->back()->with('success', 'Transfer stok ditandai sebagai dalam perjalanan.');
    }

    public function receive(ProcessStockTransferRequest $request, StockTransfer $transfer)
    {
        $this->stockTransferService->completeTransfer($transfer, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Transfer stok telah diterima dan stok telah disesuaikan.');
    }
}
