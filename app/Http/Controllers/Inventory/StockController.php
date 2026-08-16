<?php

namespace App\Http\Controllers\Inventory;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Jobs\Inventory\ExportStockJob;
use App\Jobs\Inventory\ImportStockJob;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     * Shows current stock position per item, per outlet.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $outletId = $request->get('outlet_id');

        // Summary Card
        $summary = [
            'total_item' => InventoryItem::where('business_id', $businessId)->where('is_active', true)->where('track_inventory', true)->count(),
            'total_nilai_stok' => (int) InventoryCostLayer::whereHas('outlet', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })->when($outletId, function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })->sum(DB::raw('qty_remaining * purchase_price')),
            'stok_menipis' => InventoryBalance::where('inventory_balances.business_id', $businessId)
                ->when($outletId, fn ($q) => $q->where('inventory_balances.outlet_id', $outletId))
                ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
                ->where('inventory_items.track_inventory', true)
                ->whereRaw('inventory_balances.current_stock > 0')
                ->whereRaw('inventory_balances.current_stock <= inventory_items.minimum_stock')
                ->count(),
            'stok_habis' => InventoryBalance::where('inventory_balances.business_id', $businessId)
                ->when($outletId, fn ($q) => $q->where('inventory_balances.outlet_id', $outletId))
                ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
                ->where('inventory_items.track_inventory', true)
                ->where('inventory_balances.current_stock', '<=', 0)
                ->count(),
        ];

        $stockQuery = InventoryBalance::query()
            ->where('inventory_balances.business_id', $businessId)
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoin('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->leftJoin('products', 'inventory_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select([
                'inventory_balances.id',
                'inventory_balances.outlet_id',
                'inventory_balances.inventory_item_id',
                'inventory_balances.current_stock',
                'inventory_items.name as item_name',
                'inventory_items.item_type',
                'inventory_items.sku',
                'inventory_items.minimum_stock',
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
                $q->where('inventory_items.name', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.sku', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.barcode', 'ilike', "%{$search}%");
            });
        }

        if ($request->get('item_type')) {
            $stockQuery->where('inventory_items.item_type', $request->get('item_type'));
        }

        if ($request->get('category_id')) {
            $stockQuery->where('products.product_category_id', $request->get('category_id'));
        }

        if ($request->get('stock_status')) {
            $status = $request->get('stock_status');
            if ($status === 'aman') {
                $stockQuery->whereRaw('inventory_balances.current_stock > inventory_items.minimum_stock');
            } elseif ($status === 'menipis') {
                $stockQuery->whereRaw('inventory_balances.current_stock > 0')
                    ->whereRaw('inventory_balances.current_stock <= inventory_items.minimum_stock');
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

        $sort = $request->get('sort', 'inventory_items.name');
        $direction = $request->get('direction', 'asc');

        $stocks = $stockQuery
            ->selectRaw('inventory_balances.current_stock < inventory_items.minimum_stock as is_low_stock')
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

    public function updateBarcode(Request $request, $id)
    {
        $balance = InventoryBalance::where('business_id', Auth::user()->business_id)
            ->findOrFail($id);

        $request->validate([
            'barcode' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($balance->inventory_item_id);

        $exists = InventoryItem::where('business_id', Auth::user()->business_id)
            ->where('barcode', $request->barcode)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Barcode sudah digunakan oleh produk lain.'], 422);
        }

        $item->update(['barcode' => $request->barcode]);

        return response()->json(['message' => 'Barcode berhasil diperbarui.', 'barcode' => $item->barcode]);
    }

    public function updateSku(Request $request, $id)
    {
        $balance = InventoryBalance::where('business_id', Auth::user()->business_id)
            ->findOrFail($id);

        $request->validate([
            'sku' => 'required|string|max:100',
        ]);

        $item = InventoryItem::findOrFail($balance->inventory_item_id);

        $exists = InventoryItem::where('business_id', Auth::user()->business_id)
            ->where('sku', $request->sku)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'SKU sudah digunakan oleh produk lain.'], 422);
        }

        $item->update(['sku' => $request->sku]);

        return response()->json(['message' => 'SKU berhasil diperbarui.', 'sku' => $item->sku]);
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

        $export = new class($headers, $dummyData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
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

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
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

    public function storeInitialStock(Request $request, $id)
    {
        $balance = InventoryBalance::where('business_id', Auth::user()->business_id)
            ->findOrFail($id);

        if ($balance->current_stock > 0) {
            return response()->json(['message' => 'Stok awal tidak dapat ditambahkan karena stok saat ini lebih dari 0.'], 422);
        }

        $movementCount = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->where('outlet_id', $balance->outlet_id)
            ->count();

        if ($movementCount > 0) {
            return response()->json(['message' => 'Stok awal hanya dapat diinput jika belum pernah ada mutasi sama sekali.'], 422);
        }

        $request->validate([
            'qty' => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $balance->current_stock = $request->qty;
            $balance->save();

            $movement = InventoryMovement::create([
                'business_id' => Auth::user()->business_id,
                'outlet_id' => $balance->outlet_id,
                'inventory_item_id' => $balance->inventory_item_id,
                'movement_type' => \App\Enums\InventoryMovementType::Adjustment->value,
                'qty_change' => $request->qty,
                'stock_before' => 0,
                'stock_after' => $request->qty,
                'description' => 'Input Stok Awal',
                'created_by' => Auth::id(),
            ]);

            InventoryCostLayer::create([
                'inventory_item_id' => $balance->inventory_item_id,
                'outlet_id' => $balance->outlet_id,
                'purchase_price' => $request->purchase_price,
                'qty_purchased' => $request->qty,
                'qty_remaining' => $request->qty,
                'reference_id' => $movement->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Stok awal berhasil ditambahkan.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menambahkan stok awal: '.$e->getMessage()], 500);
        }
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
            ->leftJoin('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->leftJoin('products', 'inventory_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select([
                'inventory_balances.id',
                'inventory_balances.current_stock',
                'inventory_items.name as item_name',
                'inventory_items.item_type',
                'inventory_items.sku',
                'inventory_items.minimum_stock',
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
                $q->where('inventory_items.name', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.sku', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.barcode', 'ilike', "%{$search}%");
            });
        }

        if ($request->get('item_type')) {
            $stockQuery->where('inventory_items.item_type', $request->get('item_type'));
        }

        if ($request->get('category_id')) {
            $stockQuery->where('products.product_category_id', $request->get('category_id'));
        }

        if ($request->get('stock_status')) {
            $status = $request->get('stock_status');
            if ($status === 'aman') {
                $stockQuery->whereRaw('inventory_balances.current_stock > inventory_items.minimum_stock');
            } elseif ($status === 'menipis') {
                $stockQuery->whereRaw('inventory_balances.current_stock > 0')
                    ->whereRaw('inventory_balances.current_stock <= inventory_items.minimum_stock');
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

        $sort = $request->get('sort', 'inventory_items.name');
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
