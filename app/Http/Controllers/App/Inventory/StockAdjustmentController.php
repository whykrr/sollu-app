<?php

namespace App\Http\Controllers\App\Inventory;

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\IndexStockAdjustmentRequest;
use App\Http\Requests\App\Inventory\RejectStockAdjustmentRequest;
use App\Http\Requests\App\Inventory\StoreStockAdjustmentRequest;
use App\Models\Inventory\StockAdjustment;
use App\Services\App\Inventory\StockAdjustmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of the stock adjustments.
     */
    public function index(IndexStockAdjustmentRequest $request)
    {
        $businessId = Auth::user()->business_id;

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $filterData = $request->only(['search', 'status', 'reason', 'outlet_id', 'date_from', 'date_to', 'preset']);
        $filterData['outlet_id'] = SelectedOutlet::resolveEffectiveOutletId($request->user(), $filterData['outlet_id'] ?? null);

        $adjustments = StockAdjustment::query()
            ->where('business_id', $businessId)
            ->with(['outlet', 'creator', 'approver'])
            ->withCount('items')
            ->filters($filterData)
            ->sortable($sort, $direction)
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        $params = [
            ...$filterData,
            'sort' => $sort,
            'direction' => $direction,
        ];

        return inertia('Inventory/Adjustment/Index', [
            'adjustments' => $adjustments,
            'filters' => $params,
            'params' => $params,
        ]);
    }

    /**
     * Store a newly created draft adjustment.
     */
    public function store(StoreStockAdjustmentRequest $request, StockAdjustmentService $service)
    {
        $service->create($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Penyesuaian berhasil disimpan sebagai draf. Menunggu persetujuan.');
    }

    /**
     * Display a specific adjustment detail for Axios loading.
     */
    public function show($id)
    {
        Gate::authorize('inventory.adjustment.read');

        $businessId = Auth::user()->business_id;

        $adjustmentDetail = StockAdjustment::with(['outlet', 'creator', 'approver', 'items.inventoryItem.uom'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return response()->json($adjustmentDetail);
    }

    /**
     * Approve a draft adjustment.
     */
    public function approve(StockAdjustment $stock_adjustment, StockAdjustmentService $service)
    {
        $this->authorize('inventory.adjustment.approve');

        try {
            $service->approve($stock_adjustment, Auth::user());

            return redirect()->back()->with('success', 'Penyesuaian stok berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    /**
     * Reject a draft adjustment.
     */
    public function reject(RejectStockAdjustmentRequest $request, StockAdjustment $stock_adjustment, StockAdjustmentService $service)
    {
        try {
            $service->reject($stock_adjustment, $request->input('notes'), Auth::user());

            return redirect()->back()->with('success', 'Penyesuaian stok berhasil ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    /**
     * Void an approved adjustment.
     */
    public function void(StockAdjustment $stock_adjustment, StockAdjustmentService $service)
    {
        Gate::authorize('inventory.adjustment.void');

        // Middleware `EnsureStockNotFrozen` is applied on route

        try {
            $service->void($stock_adjustment, Auth::user());

            return redirect()->back()->with('success', 'Penyesuaian stok berhasil dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    /**
     * Export to PDF (Berita Acara).
     */
    public function exportPdf(string $id)
    {
        Gate::authorize('inventory.adjustment.export');

        $businessId = Auth::user()->business_id;

        $adjustment = StockAdjustment::with([
            'outlet',
            'creator',
            'approver',
            'items.inventoryItem.uom',
        ])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        $business = Auth::user()->business;

        $pdf = Pdf::loadView('pdf.stock-adjustment', [
            'adjustment' => $adjustment,
            'business' => $business,
            'outlet' => $adjustment->outlet,
            'title' => 'PENYESUAIAN STOK',
            'subtitle' => 'Nomor: '.$adjustment->adjustment_number,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Penyesuaian_Stok_'.$adjustment->adjustment_number.'.pdf');
    }
}
