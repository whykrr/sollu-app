<?php

namespace App\Http\Controllers\App\Inventory;

use App\Constants\FlashDataVariable;
use App\Enums\DatePresetEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\Transfer\GetStockTransferRequest;
use App\Http\Requests\App\Inventory\Transfer\ProcessStockTransferRequest;
use App\Http\Requests\App\Inventory\Transfer\StoreStockTransferRequest;
use App\Http\Requests\App\Inventory\Transfer\UpdateStockTransferRequest;
use App\Models\Inventory\StockTransfer;
use App\Services\App\Inventory\StockTransferService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $stockTransferService
    ) {}

    public function index(GetStockTransferRequest $request)
    {
        $businessId = Auth::user()->business_id;

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $dateRange = DatePresetEnum::resolveRange(
            $request->input('preset'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        $filterData = array_merge(
            $request->only(['search', 'status', 'from_outlet_id', 'to_outlet_id', 'outlet_id']),
            [
                'preset' => $dateRange['preset'],
                'date_from' => $dateRange['start_date'],
                'date_to' => $dateRange['end_date'],
            ]
        );

        $transfers = StockTransfer::query()
            ->where('business_id', $businessId)
            ->with(['fromOutlet:id,name', 'toOutlet:id,name', 'requester:id,name'])
            ->withCount('items')
            ->filters($filterData)
            ->sortable($sort, $direction)
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        $params = array_merge($filterData, [
            'sort' => $sort,
            'direction' => $direction,
        ]);

        return inertia('Inventory/Transfer/Index', [
            'transfers' => $transfers,
            'filters' => $params,
            'params' => $params,
        ]);
    }

    public function store(StoreStockTransferRequest $request)
    {
        try {
            $this->stockTransferService->createTransfer($request->validated(), $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Permintaan mutasi stok berhasil disimpan sebagai draf. Menunggu persetujuan.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function show(StockTransfer $transfer)
    {
        Gate::authorize(PermissionEnum::INVENTORY_TRANSFER_READ->value);

        $transfer->load([
            'fromOutlet:id,name,is_stock_frozen',
            'toOutlet:id,name,is_stock_frozen',
            'requester:id,name',
            'approver:id,name',
            'receiver:id,name',
            'items.inventoryItem.uom:id,name,code',
        ]);

        return response()->json([
            'data' => $transfer,
        ]);
    }

    public function exportPdf(StockTransfer $transfer)
    {
        Gate::authorize(PermissionEnum::INVENTORY_TRANSFER_READ->value);

        $transfer->load([
            'fromOutlet',
            'toOutlet',
            'requester',
            'approver',
            'receiver',
            'items.inventoryItem.uom',
        ]);

        $pdf = Pdf::loadView('pdf.inventory.transfer-detail', [
            'data' => $transfer,
            'business' => Auth::user()->business,
            'outlet' => $transfer->fromOutlet,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Transfer_Stok_'.$transfer->transfer_number.'.pdf');
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $transfer)
    {
        try {
            $this->stockTransferService->updateTransfer($transfer, $request->validated());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Mutasi stok berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function approve(StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_APPROVE->value);

        try {
            $this->stockTransferService->approveTransfer($transfer, Auth::user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Mutasi stok berhasil disetujui.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function reject(Request $request, StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_APPROVE->value);

        $request->validate(['notes' => 'required|string']);

        try {
            $this->stockTransferService->rejectTransfer($transfer, $request->only('notes'), Auth::user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Mutasi stok berhasil ditolak.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function ship(StockTransfer $transfer)
    {
        $this->authorize(PermissionEnum::INVENTORY_TRANSFER_SHIP->value);

        try {
            $this->stockTransferService->shipTransfer($transfer, Auth::user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Barang mutasi berhasil ditandai dalam perjalanan.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function receive(ProcessStockTransferRequest $request, StockTransfer $transfer)
    {
        try {
            $this->stockTransferService->completeTransfer($transfer, $request->validated(), Auth::user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Barang mutasi telah diterima dan stok telah disesuaikan.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }
}
