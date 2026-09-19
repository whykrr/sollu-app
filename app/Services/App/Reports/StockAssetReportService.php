<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockAssetReportService
{
    /**
     * Dapatkan Laporan Stok & Valuasi Aset Persediaan untuk periode tertentu.
     */
    public function getReport(
        string|array $outletId,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): LengthAwarePaginator {
        $outletIds = array_filter((array) $outletId);
        $perPage = (int) ($filters['perpage'] ?? 15);
        $search = $filters['search'] ?? null;

        // 1. Ambil query master item persediaan
        $query = DB::table('inventory_items')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('inventory_items.name', 'ilike', "%{$search}%")
                        ->orWhere('inventory_items.sku', 'ilike', "%{$search}%");
                });
            })
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                'inventory_items.sku',
                'inventory_items.item_type'
            )
            ->orderBy('inventory_items.name');

        $paginator = $query->paginate($perPage);
        $itemIds = $paginator->getCollection()->pluck('item_id')->toArray();

        if (empty($itemIds)) {
            return $paginator;
        }

        // 2. Ambil saldo stok saat ini dan average_cost dari inventory_balances
        $balances = DB::table('inventory_balances')
            ->whereIn('inventory_item_id', $itemIds)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->select(
                'inventory_item_id as item_id',
                DB::raw('SUM(current_stock) as current_stock'),
                DB::raw('AVG(average_cost) as average_cost'),
                DB::raw('SUM(total_value) as total_value')
            )
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('item_id');

        // 3. Ambil mutasi dalam periode [startDate, endDate]
        $movementsInPeriod = DB::table('inventory_movements')
            ->whereIn('inventory_item_id', $itemIds)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'inventory_item_id as item_id',
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) as qty_in'),
                DB::raw('SUM(CASE WHEN qty_change < 0 THEN ABS(qty_change) ELSE 0 END) as qty_out'),
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN total_cost ELSE 0 END) as cost_in'),
                DB::raw('SUM(CASE WHEN qty_change < 0 THEN total_cost ELSE 0 END) as cost_out')
            )
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('item_id');

        // 4. Ambil mutasi setelah periode (> endDate) untuk rekonstruksi saldo akhir tepat pada endDate
        $movementsAfterPeriod = DB::table('inventory_movements')
            ->whereIn('inventory_item_id', $itemIds)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->where('created_at', '>', $endDate)
            ->select(
                'inventory_item_id as item_id',
                DB::raw('SUM(qty_change) as net_change_after'),
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN total_cost ELSE -total_cost END) as net_cost_after')
            )
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('item_id');

        $paginator->getCollection()->transform(function ($item) use (
            $balances,
            $movementsInPeriod,
            $movementsAfterPeriod
        ) {
            $bal = $balances[$item->item_id] ?? null;
            $currentStock = (float) ($bal?->current_stock ?? 0);
            $avgCost = (float) ($bal?->average_cost ?? 0);

            $inPeriod = $movementsInPeriod[$item->item_id] ?? null;
            $qtyIn = (float) ($inPeriod?->qty_in ?? 0);
            $qtyOut = (float) ($inPeriod?->qty_out ?? 0);
            $costIn = (float) ($inPeriod?->cost_in ?? 0);
            $costOut = (float) ($inPeriod?->cost_out ?? 0);

            $afterPeriod = $movementsAfterPeriod[$item->item_id] ?? null;
            $netChangeAfter = (float) ($afterPeriod?->net_change_after ?? 0);

            // Rekonstruksi Saldo Akhir pada saat $endDate
            $closingStock = $currentStock - $netChangeAfter;
            // Rekonstruksi Saldo Awal pada saat $startDate
            $startingStock = $closingStock - $qtyIn + $qtyOut;

            $closingAssetValue = max(0, $closingStock * $avgCost);
            $startingAssetValue = max(0, $startingStock * $avgCost);

            return (object) [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'sku' => $item->sku,
                'item_type' => $item->item_type,
                'starting_stock' => (float) $startingStock,
                'stock_in' => (float) $qtyIn,
                'stock_out' => (float) $qtyOut,
                'closing_stock' => (float) $closingStock,
                'unit_cost' => (float) $avgCost,
                'starting_asset_value' => (float) $startingAssetValue,
                'inflow_asset_value' => (float) $costIn,
                'outflow_asset_value' => (float) $costOut,
                'closing_asset_value' => (float) $closingAssetValue,
            ];
        });

        return $paginator;
    }

    /**
     * Dapatkan ringkasan total nilai aset persediaan (Summary KPIs).
     */
    public function getValuationSummary(
        string|array $outletId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $outletIds = array_filter((array) $outletId);

        $balances = DB::table('inventory_balances')
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->select(
                DB::raw('COUNT(DISTINCT inventory_item_id) as total_items'),
                DB::raw('SUM(current_stock) as total_current_stock'),
                DB::raw('SUM(total_value) as total_current_value')
            )
            ->first();

        $movements = DB::table('inventory_movements')
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) as total_qty_in'),
                DB::raw('SUM(CASE WHEN qty_change < 0 THEN ABS(qty_change) ELSE 0 END) as total_qty_out'),
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN total_cost ELSE 0 END) as total_cost_in'),
                DB::raw('SUM(CASE WHEN qty_change < 0 THEN total_cost ELSE 0 END) as total_cost_out')
            )
            ->first();

        return [
            'total_items' => (int) ($balances?->total_items ?? 0),
            'total_current_stock' => (float) ($balances?->total_current_stock ?? 0),
            'total_asset_value' => (float) ($balances?->total_current_value ?? 0),
            'period_qty_in' => (float) ($movements?->total_qty_in ?? 0),
            'period_qty_out' => (float) ($movements?->total_qty_out ?? 0),
            'period_cost_in' => (float) ($movements?->total_cost_in ?? 0),
            'period_cost_out' => (float) ($movements?->total_cost_out ?? 0),
        ];
    }
}
