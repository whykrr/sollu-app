<?php

namespace App\Http\Controllers\App\Inventory;

use App\Constants\FlashDataVariable;
use App\Enums\InventoryMovementType;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\BatchInitialStockRequest;
use App\Jobs\Inventory\ExportStockJob;
use App\Jobs\Inventory\ImportStockJob;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Inventory\StockFreezeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     * Shows current stock position per item, per outlet.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $outletId = SelectedOutlet::resolveEffectiveOutletId($request->user(), $request->get('outlet_id') ?: $request->get('outlet'));

        // Summary Card
        $summary = [
            'total_item' => InventoryItem::where('business_id', $businessId)
                ->where('is_active', true)
                ->whereHas('productItem', fn ($q) => $q->where('track_inventory', true))
                ->count(),
            'total_nilai_stok' => (int) InventoryCostLayer::whereHas('outlet', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })->when($outletId, function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })->sum(DB::raw('qty_remaining * purchase_price')),
            'stok_menipis' => InventoryBalance::where('inventory_balances.business_id', $businessId)
                ->when($outletId, fn ($q) => $q->where('inventory_balances.outlet_id', $outletId))
                ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
                ->join('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
                ->where('product_items.track_inventory', true)
                ->whereRaw('inventory_balances.current_stock > 0')
                ->whereRaw('inventory_balances.current_stock <= inventory_balances.minimum_stock')
                ->count(),
            'stok_habis' => InventoryBalance::where('inventory_balances.business_id', $businessId)
                ->when($outletId, fn ($q) => $q->where('inventory_balances.outlet_id', $outletId))
                ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
                ->join('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
                ->where('product_items.track_inventory', true)
                ->where('inventory_balances.current_stock', '<=', 0)
                ->count(),
        ];

        $stockQuery = InventoryBalance::query()
            ->where('inventory_balances.business_id', $businessId)
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->join('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
            ->leftJoin('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->leftJoin('products', 'product_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select([
                'inventory_balances.id',
                'inventory_balances.outlet_id',
                'inventory_balances.inventory_item_id',
                'inventory_balances.current_stock',
                'inventory_balances.minimum_stock',
                DB::raw('COALESCE(inventory_items.name, product_items.name) as item_name'),
                'product_items.item_type',
                'product_items.sku',
                'inventory_items.is_active',
                'uoms.code as uom',
                'outlets.name as outlet_name',
                'product_categories.name as category_name',
                'inventory_balances.updated_at',
            ]);

        if ($outletId) {
            $stockQuery->where('inventory_balances.outlet_id', $outletId);
        }

        if ($request->get('search')) {
            $search = $request->get('search');
            $stockQuery->where(function ($q) use ($search) {
                $q->whereLike('inventory_items.name', "%{$search}%")
                    ->orWhereLike('product_items.sku', "%{$search}%")
                    ->orWhereLike('product_items.barcode', "%{$search}%");
            });
        }

        if ($request->get('item_type')) {
            $stockQuery->where('product_items.item_type', $request->get('item_type'));
        }

        if ($request->get('category_id')) {
            $stockQuery->where('products.product_category_id', $request->get('category_id'));
        }

        if ($request->get('stock_status')) {
            $status = $request->get('stock_status');
            if ($status === 'aman') {
                $stockQuery->whereRaw('inventory_balances.current_stock > inventory_balances.minimum_stock');
            } elseif ($status === 'menipis') {
                $stockQuery->whereRaw('inventory_balances.current_stock > 0')
                    ->whereRaw('inventory_balances.current_stock <= inventory_balances.minimum_stock');
            } elseif ($status === 'habis') {
                $stockQuery->where('inventory_balances.current_stock', '<=', 0);
            }
        }

        if ($request->boolean('is_active_only')) {
            $stockQuery->where('inventory_items.is_active', true);
        }

        if ($request->boolean('in_stock_only')) {
            $stockQuery->where('inventory_balances.current_stock', '>', 0);
        }

        $sort = $request->get('sort', 'product_items.name');
        if ($sort === 'inventory_items.name' || $sort === 'name') {
            $sort = 'product_items.name';
        }
        $direction = $request->get('direction', 'asc');

        $stocks = $stockQuery
            ->selectRaw('inventory_balances.current_stock <= inventory_balances.minimum_stock as is_low_stock')
            ->orderBy($sort, $direction)
            ->paginate($request->get('per_page', 20))
            ->withQueryString()
            ->through(function ($item) {
                // Menyuntikkan format minimum_stock agar terbaca di frontend tanpa mengubah model
                $item->minimum_stock_formatted = $item->formatQuantity($item->minimum_stock);

                return $item;
            });

        $categories = ProductCategory::currentBusiness()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return inertia('Inventory/Stock/Index', [
            'stocks' => $stocks,
            'categories' => $categories,
            'summary' => $summary,
            'filters' => [
                ...$request->only(['search', 'outlet_id', 'item_type', 'category_id', 'stock_status', 'is_active_only', 'in_stock_only']),
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Detail API: Get item header, movements, and chart data
     */
    public function show(Request $request, $id)
    {
        $businessId = Auth::user()->business_id;

        $balance = InventoryBalance::where('business_id', $businessId)
            ->where('id', $id)
            ->firstOrFail();

        $item = InventoryItem::where('business_id', $businessId)
            ->with(['uom', 'product.category'])
            ->findOrFail($balance->inventory_item_id);

        // Movements for the specific outlet (latest 50)
        $movements = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->where('outlet_id', $balance->outlet_id)
            ->with(['outlet', 'creator', 'reference'])
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        // Chart Data for the last 30 days
        $thirtyDaysAgo = now()->subDays(30);
        $chartMovements = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->where('outlet_id', $balance->outlet_id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->orderBy('created_at')
            ->get();

        $dailyData = $chartMovements->groupBy(function ($m) {
            return $m->created_at->format('Y-m-d');
        })->map(function ($dayMovements) {
            return $dayMovements->sum('qty_change');
        });

        $labels = [];
        $data = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            $data[] = $dailyData->get($date, 0);
        }

        return response()->json([
            'item' => $item,
            'current_balance' => $balance,
            'movements' => $movements,
            'chart' => [
                'labels' => $labels,
                'data' => $data,
            ],
        ]);
    }

    public function updateMinimumStock(Request $request, $id)
    {
        $balance = InventoryBalance::where('business_id', Auth::user()->business_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $balance->update([
            'minimum_stock' => (float) $validated['minimum_stock'],
        ]);

        return response()->json([
            'message' => 'Minimum stok berhasil diperbarui.',
            'minimum_stock' => (float) $balance->minimum_stock,
            'minimum_stock_formatted' => $balance->minimum_stock_formatted,
        ]);
    }

    public function importTemplate()
    {
        $headers = [
            'Outlet',
            'Nama',
            'SKU',
            'Barcode',
            'Tipe Item',
            'Kategori',
            'Satuan',
            'Minimum Stok',
            'Stok Awal',
            'Harga Beli',
            'Stok Saat Ini',
            'Status',
        ];

        $dummyData = [
            'Outlet Utama',
            'Contoh Produk A',
            'SKU-001',
            '8991234567890',
            'Produk',
            'Minuman',
            'Cangkir',
            '5',
            '',
            '',
            '10',
            'Aman',
        ];

        $export = new class($headers, $dummyData) implements FromArray, WithHeadings
        {
            private $headers;

            private $dummyData;

            public function __construct($headers, $dummyData)
            {
                $this->headers = $headers;
                $this->dummyData = $dummyData;
            }

            public function array(): array
            {
                return [$this->dummyData];
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        $filename = 'template_'.strtolower(class_basename($this)).'.xlsx';

        return Excel::download($export, $filename);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportStockJob::dispatch(
            Auth::user(),
            $path,
            Auth::user()->business_id
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses impor stok CSV sedang berjalan di latar belakang. Notifikasi akan masuk jika sudah selesai.'
        );
    }

    /**
     * Cari barang-barang inventori yang memenuhi syarat untuk diisi atau diperbarui stok awalnya.
     */
    public function searchEligibleItems(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $outletId = $request->get('outlet_id') ?: SelectedOutlet::resolveEffectiveOutletId(Auth::user(), $request->get('outlet_id'));
        $search = $request->get('query') ?: $request->get('search');

        if (! $outletId) {
            return response()->json(['message' => 'Outlet tujuan wajib dipilih.'], 422);
        }

        $outlet = Outlet::where('business_id', $businessId)->findOrFail($outletId);

        $query = InventoryItem::query()
            ->where('inventory_items.business_id', $businessId)
            ->where('inventory_items.is_active', true)
            ->joinProductItem()
            ->where('product_items.track_inventory', true)
            ->with(['uom:id,name,code', 'productItem', 'balances' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            }])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereLike('inventory_items.name', "%{$search}%")
                        ->orWhereLike('product_items.sku', "%{$search}%")
                        ->orWhereLike('product_items.barcode', "%{$search}%");
                });
            });

        $items = $query->limit(60)->get();

        $eligible = $items->filter(function ($item) use ($outletId) {
            $hasOtherMovements = InventoryMovement::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outletId)
                ->where('movement_type', '!=', InventoryMovementType::InitialStock)
                ->exists();

            return ! $hasOtherMovements;
        })->map(function ($item) use ($outletId) {
            $balance = $item->balances->first();
            $initialMovement = InventoryMovement::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outletId)
                ->where('movement_type', InventoryMovementType::InitialStock)
                ->first();

            $isInitialOnly = (bool) $initialMovement;
            $currentStock = $balance ? (float) $balance->current_stock : 0.0;
            $currentCost = $balance ? (float) ($balance->average_cost ?: $balance->last_cost ?: 0.0) : 0.0;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku ?: ($item->productItem?->sku ?? ''),
                'barcode' => $item->barcode ?: ($item->productItem?->barcode ?? ''),
                'uom' => $item->uom?->name ?: ($item->uom?->code ?: 'Pcs'),
                'current_stock' => $currentStock,
                'current_cost' => $currentCost,
                'is_initial_only' => $isInitialOnly,
            ];
        })->values();

        return response()->json($eligible);
    }

    /**
     * Simpan stok awal massal untuk outlet yang dipilih.
     */
    public function storeBatchInitialStock(
        BatchInitialStockRequest $request,
        InventoryCostingService $costingService,
        StockFreezeService $stockFreezeService
    ) {
        $validated = $request->validated();
        $user = Auth::user();
        $business = $user->business;

        $outlet = Outlet::where('business_id', $business->id)->findOrFail($validated['outlet_id']);
        $stockFreezeService->assertNotFrozen($outlet);

        DB::transaction(function () use ($validated, $business, $outlet, $user, $costingService) {
            foreach ($validated['items'] as $itemData) {
                $invItem = InventoryItem::where('business_id', $business->id)
                    ->findOrFail($itemData['inventory_item_id']);

                $hasOtherMovements = InventoryMovement::where('inventory_item_id', $invItem->id)
                    ->where('outlet_id', $outlet->id)
                    ->where('movement_type', '!=', InventoryMovementType::InitialStock)
                    ->exists();

                if ($hasOtherMovements) {
                    throw new \InvalidArgumentException("Barang '{$invItem->name}' sudah memiliki riwayat mutasi operasional lanjutan sehingga stok awal tidak dapat diubah lagi.");
                }

                $hasInitialMovement = InventoryMovement::where('inventory_item_id', $invItem->id)
                    ->where('outlet_id', $outlet->id)
                    ->where('movement_type', InventoryMovementType::InitialStock)
                    ->exists();

                if ($hasInitialMovement) {
                    $costingService->updateInitialStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        newQty: (float) $itemData['qty'],
                        newUnitCost: (float) $itemData['purchase_price'],
                        user: $user
                    );
                } else {
                    $costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: (float) $itemData['qty'],
                        unitCost: (float) $itemData['purchase_price'],
                        movementType: InventoryMovementType::InitialStock,
                        reference: null,
                        description: 'Inisiasi Stok Awal',
                        user: $user
                    );
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Stok awal berhasil disimpan.',
            ]);
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Stok awal berhasil disimpan.'
        );
    }

    public function exportPdf(Request $request, $id)
    {
        $businessId = Auth::user()->business_id;

        $balance = InventoryBalance::where('business_id', $businessId)
            ->findOrFail($id);

        $item = InventoryItem::where('business_id', $businessId)
            ->with(['uom', 'product.category'])
            ->findOrFail($balance->inventory_item_id);

        $outlet = Outlet::where('business_id', $businessId)->findOrFail($balance->outlet_id);

        $thirtyDaysAgo = now()->subDays(30);
        $movements = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->where('outlet_id', $balance->outlet_id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->with(['creator', 'reference'])
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.inventory.stock-movements', [
            'item' => $item,
            'outlet' => $outlet,
            'movements' => $movements,
            'business' => Auth::user()->business,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Riwayat_Mutasi_'.$item->sku.'_'.now()->format('Ymd').'.pdf');
    }

    public function exportCsv(Request $request)
    {
        ExportStockJob::dispatch(
            Auth::user(),
            Auth::user()->business_id,
            $request->all()
        );

        return redirect()->back()->with('success', 'Ekspor stok (CSV) sedang diproses di latar belakang. Notifikasi akan masuk jika sudah selesai.');
    }

    public function exportPdfList(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $outletId = $request->get('outlet_id');

        $stockQuery = InventoryBalance::query()
            ->where('inventory_balances.business_id', $businessId)
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->join('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
            ->leftJoin('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->leftJoin('products', 'product_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select([
                'inventory_balances.id',
                'inventory_balances.current_stock',
                'inventory_balances.minimum_stock',
                DB::raw('COALESCE(inventory_items.name, product_items.name) as item_name'),
                'product_items.item_type',
                'product_items.sku',
                'uoms.code as uom',
                'outlets.name as outlet_name',
                'product_categories.name as category_name',
            ]);

        if ($outletId) {
            $stockQuery->where('inventory_balances.outlet_id', $outletId);
        }

        if ($request->get('search')) {
            $search = $request->get('search');
            $stockQuery->where(function ($q) use ($search) {
                $q->whereLike('inventory_items.name', "%{$search}%")
                    ->orWhereLike('product_items.sku', "%{$search}%")
                    ->orWhereLike('product_items.barcode', "%{$search}%");
            });
        }

        if ($request->get('item_type')) {
            $stockQuery->where('product_items.item_type', $request->get('item_type'));
        }

        if ($request->get('category_id')) {
            $stockQuery->where('products.product_category_id', $request->get('category_id'));
        }

        if ($request->get('stock_status')) {
            $status = $request->get('stock_status');
            if ($status === 'aman') {
                $stockQuery->whereRaw('inventory_balances.current_stock > inventory_balances.minimum_stock');
            } elseif ($status === 'menipis') {
                $stockQuery->whereRaw('inventory_balances.current_stock > 0')
                    ->whereRaw('inventory_balances.current_stock <= inventory_balances.minimum_stock');
            } elseif ($status === 'habis') {
                $stockQuery->where('inventory_balances.current_stock', '<=', 0);
            }
        }

        if ($request->boolean('is_active_only')) {
            $stockQuery->where('inventory_items.is_active', true);
        }

        if ($request->boolean('in_stock_only')) {
            $stockQuery->where('inventory_balances.current_stock', '>', 0);
        }

        $sort = $request->get('sort', 'product_items.name');
        if ($sort === 'inventory_items.name' || $sort === 'name') {
            $sort = 'product_items.name';
        }
        $direction = $request->get('direction', 'asc');

        $stocks = $stockQuery->orderBy($sort, $direction)->limit(1000)->get();

        $outlet = $outletId ? Outlet::where('business_id', $businessId)->find($outletId) : null;

        $pdf = Pdf::loadView('pdf.inventory.stock-list', [
            'stocks' => $stocks,
            'business' => Auth::user()->business,
            'outlet' => $outlet,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Stok_'.now()->format('Ymd_His').'.pdf');
    }
}
