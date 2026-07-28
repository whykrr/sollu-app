<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
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
        $outletId   = $request->get('outlet_id');

        $outlets = Outlet::where('business_id', $businessId)
            ->active()
            ->select('id', 'name')
            ->get();

        // Summary Card
        $summary = [
            'total_item'       => InventoryItem::where('business_id', $businessId)->where('is_active', true)->where('track_inventory', true)->count(),
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

        $stocks = $stockQuery
            ->selectRaw('inventory_balances.current_stock < inventory_items.minimum_stock as is_low_stock')
            ->orderBy('inventory_items.name')
            ->paginate($request->get('per_page', 20))
            ->withQueryString()
            ->through(function ($item) {
                // Menyuntikkan format minimum_stock agar terbaca di frontend tanpa mengubah model
                $item->minimum_stock_formatted = $item->formatQuantity($item->minimum_stock);
                return $item;
            });

        $categories = ProductCategory::where('business_id', $businessId)->select('id', 'name')->get();

        return inertia('Inventory/Stock/Index', [
            'stocks'     => $stocks,
            'outlets'    => $outlets,
            'categories' => $categories,
            'summary'    => $summary,
            'filters'    => $request->only(['search', 'outlet_id', 'item_type', 'category_id', 'stock_status', 'is_active_only', 'in_stock_only']),
        ]);
    }

    /**
     * Detail API: Get item header and stock balances per outlet
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

        $balances = InventoryBalance::where('inventory_item_id', $item->id)
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select('inventory_balances.*', 'outlets.name as outlet_name')
            ->get();

        return response()->json([
            'item'            => $item,
            'balances'        => $balances,
            'current_balance' => $balance,
        ]);
    }

    /**
     * Detail API: Get paginated stock movements
     */
    public function movements(Request $request, $id)
    {
        $businessId = Auth::user()->business_id;
        $balance    = InventoryBalance::where('business_id', $businessId)
            ->where('id', $id)
            ->firstOrFail();

        $movements = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->when($request->get('outlet_id'), fn ($q) => $q->where('outlet_id', $request->get('outlet_id')))
            ->with(['outlet', 'creator', 'reference'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($movements);
    }

    /**
     * Detail API: Get 30-day stock chart data
     */
    public function chart(Request $request, $id)
    {
        $businessId = Auth::user()->business_id;
        $balance    = InventoryBalance::where('business_id', $businessId)
            ->where('id', $id)
            ->firstOrFail();

        $thirtyDaysAgo = now()->subDays(30);

        $movements = InventoryMovement::where('inventory_item_id', $balance->inventory_item_id)
            ->when($request->get('outlet_id'), fn ($q) => $q->where('outlet_id', $request->get('outlet_id')))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->orderBy('created_at')
            ->get();

        $dailyData = $movements->groupBy(function ($m) {
            return $m->created_at->format('Y-m-d');
        })->map(function ($dayMovements) {
            return $dayMovements->sum('qty_change');
        });

        // Ensure all 30 days are present
        $labels = [];
        $data   = [];
        for ($i = 30; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            $data[]   = $dailyData->get($date, 0);
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }
}
