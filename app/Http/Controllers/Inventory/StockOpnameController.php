<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockOpname\StoreStockOpnameRequest;
use App\Http\Requests\Inventory\StockOpname\UpdateStockOpnameRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockOpname;
use App\Models\Outlet;
use App\Services\Inventory\StockOpnameService;
use App\Services\Inventory\StockFreezeService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\StockOpnameStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('inventory.opname.read');

        $businessId = Auth::user()->business_id;
        $sort       = $request->query('sort', 'created_at');
        $direction  = $request->query('direction', 'desc');

        $filterKeys = ['search', 'status', 'outlet_id', 'date_from', 'date_to'];

        $opnames = StockOpname::currentBusiness()
            ->with(['outlet'])
            ->withCount('items')
            ->filters($request->only($filterKeys))
            ->orderBy($sort, $direction)
            ->paginate($request->query('per_page', 20))
            ->withQueryString();

        // Determine if we show Create Opname Form immediately
        $showForm = $request->query('action') === 'create';

        return inertia('Inventory/StockOpname/Index', [
            'opnames'  => $opnames,
            'filters'  => [
                ...$request->only(['search', 'status', 'outlet_id', 'date_from', 'date_to']),
                'sort'      => $sort,
                'direction' => $direction,
            ],
            'showForm' => $showForm,
        ]);
    }

    /**
     * Fetch a specific stock opname detail via Axios.
     */
    public function show($id)
    {
        $this->authorize('inventory.opname.read');

        $opname = StockOpname::currentBusiness()
            ->with(['outlet', 'creator', 'approver', 'items.inventoryItem.uom'])
            ->findOrFail($id);

        return response()->json($opname);
    }

    public function store(StoreStockOpnameRequest $request, StockOpnameService $service)
    {
        $this->authorize('inventory.opname.create');
        $service->createOpname($request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Sesi Opname berhasil dibuat. Item dapat ditambahkan.');
    }

    public function update(UpdateStockOpnameRequest $request, string $id, StockOpnameService $service)
    {
        $this->authorize('inventory.opname.update');
        $opname = StockOpname::currentBusiness()->findOrFail($id);
        $service->updateOpname($opname, $request->validated(), Auth::user());

        return redirect()->back()->with('success', 'Data Opname berhasil diperbarui (Draft diajukan).');
    }

    public function destroy(string $id)
    {
        $this->authorize('inventory.opname.delete');
        $opname = StockOpname::currentBusiness()->findOrFail($id);

        try {
            if ($opname->status !== StockOpnameStatus::InProgress) {
                return redirect()->back()->with('error', 'Hanya sesi In Progress yang dapat dibatalkan.');
            }
            $opname->delete();

            return redirect()->back()->with('success', 'Sesi Opname berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    public function approve(UpdateStockOpnameRequest $request, string $id, StockOpnameService $service)
    {
        $this->authorize('inventory.opname.approve');
        $opname = StockOpname::currentBusiness()->findOrFail($id);

        try {
            $service->completeOpname($opname, $request->validated(), Auth::user());

            return redirect()->back()->with('success', 'Opname disetujui, selisih stok telah dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    public function reject(Request $request, string $id, StockOpnameService $service)
    {
        $this->authorize('inventory.opname.approve');
        $request->validate(['notes' => 'required|string']);

        $opname = StockOpname::currentBusiness()->findOrFail($id);

        try {
            $service->rejectOpname($opname, $request->only('notes'), Auth::user());

            return redirect()->back()->with('success', 'Opname ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }
    }

    public function exportPdf(string $id)
    {
        $opname = StockOpname::with([
            'outlet',
            'creator',
            'approver',
            'items.inventoryItem.uom',
        ])
            ->currentBusiness()
            ->findOrFail($id);

        $business = Auth::user()->business;

        $pdf = Pdf::loadView('pdf.inventory.stock-opname', [
            'data'     => $opname,
            'business' => $business,
            'outlet'   => $opname->outlet,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Stock_Opname_' . $opname->opname_number . '.pdf');
    }


}
