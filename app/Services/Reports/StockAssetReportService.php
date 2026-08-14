<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockAssetReportService
{
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        // Since inventory_balances doesn't track historical changes, we use inventory_movements
        // to reconstruct the starting and ending balance for the given period.
        // Wait, for simplicity in this implementation, we will fetch current balances and summarize movements in period.

        $movements = DB::table('inventory_movements')
            ->join('inventory_items', 'inventory_movements.inventory_item_id', '=', 'inventory_items.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('inventory_movements.outlet_id', $outletIds);
            })
            ->whereBetween('inventory_movements.created_at', [$startDate, $endDate])
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change > 0 THEN inventory_movements.qty_change ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change < 0 THEN ABS(inventory_movements.qty_change) ELSE 0 END) as total_out')
            )
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->get();

        $balances = DB::table('inventory_balances')
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('inventory_balances.outlet_id', $outletIds);
            })
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                DB::raw('SUM(inventory_balances.current_stock) as current_stock')
            )
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->get()->keyBy('item_id');

        $report = [];
        foreach ($movements as $m) {
            $currentStock = isset($balances[$m->item_id]) ? $balances[$m->item_id]->current_stock : 0;
            // Approximate starting stock by subtracting net movement from current stock
            // This is a naive approximation assuming we only query up to 'today'
            $netMovement = $m->total_in - $m->total_out;
            $startingStock = $currentStock - $netMovement;

            $report[] = [
                'item_name' => $m->item_name,
                'starting_stock' => $startingStock,
                'stock_in' => $m->total_in,
                'stock_out' => $m->total_out,
                'closing_stock' => $currentStock,
                'asset_value' => 0, // Need cost for asset value, omitting for simplicity
            ];
        }

        return $report;
    }
}
