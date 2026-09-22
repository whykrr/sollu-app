<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService
{
    /**
     * Dapatkan Laporan Laba Rugi (Profit & Loss) komprehensif untuk periode tertentu.
     *
     * @param  array<string>  $outletIds
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReport(
        string $businessId,
        array $outletIds,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $outletIds = array_values(array_filter($outletIds));

        // 1. Ringkasan Penjualan (Revenue / Omset)
        $salesSummary = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('transactions.outlet_id', $outletIds))
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COALESCE(SUM(transactions.subtotal), 0) as gross_sales'),
                DB::raw('COALESCE(SUM(transactions.discount_amount), 0) as total_discounts'),
                DB::raw('COALESCE(SUM(transactions.tax_amount), 0) as total_tax'),
                DB::raw('COALESCE(SUM(transactions.service_charge_amount), 0) as total_service_charge'),
                DB::raw('COALESCE(SUM(transactions.total), 0) as net_sales'),
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->first();

        $grossSales = (float) ($salesSummary->gross_sales ?? 0);
        $totalDiscounts = (float) ($salesSummary->total_discounts ?? 0);
        $netSales = (float) ($salesSummary->net_sales ?? 0);
        $totalTax = (float) ($salesSummary->total_tax ?? 0);
        $totalServiceCharge = (float) ($salesSummary->total_service_charge ?? 0);
        $transactionCount = (int) ($salesSummary->transaction_count ?? 0);

        // 2. Ringkasan HPP (Cost of Goods Sold / COGS)
        $cogsSummary = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('transactions.outlet_id', $outletIds))
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COALESCE(SUM(transaction_items.cogs_amount), 0) as total_cogs'),
                DB::raw('COALESCE(SUM(transaction_items.qty), 0) as total_items_sold')
            )
            ->first();

        $totalCogs = (float) ($cogsSummary->total_cogs ?? 0);
        $totalItemsSold = (float) ($cogsSummary->total_items_sold ?? 0);

        // Laba Kotor (Gross Profit)
        $grossProfit = $netSales - $totalCogs;
        $grossProfitMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;

        // 3. Ringkasan Beban Persediaan Operasional (Waste & Selisih Opname)
        $inventoryLosses = DB::table('inventory_movements')
            ->where('inventory_movements.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('inventory_movements.outlet_id', $outletIds))
            ->whereBetween('inventory_movements.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.movement_type = 'adjustment_out' THEN inventory_movements.total_cost ELSE 0 END), 0) as waste_cost"),
                DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.movement_type = 'opname_deficit' THEN inventory_movements.total_cost ELSE 0 END), 0) as opname_deficit_cost"),
                DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.movement_type = 'opname_surplus' THEN inventory_movements.total_cost ELSE 0 END), 0) as opname_surplus_cost")
            )
            ->first();

        $wasteCost = (float) ($inventoryLosses->waste_cost ?? 0);
        $opnameDeficitCost = (float) ($inventoryLosses->opname_deficit_cost ?? 0);
        $opnameSurplusCost = (float) ($inventoryLosses->opname_surplus_cost ?? 0);
        $netInventoryLoss = ($wasteCost + $opnameDeficitCost) - $opnameSurplusCost;

        // Laba Bersih Operasional (Operating Profit)
        $operatingProfit = $grossProfit - $netInventoryLoss;
        $operatingProfitMargin = $netSales > 0 ? round(($operatingProfit / $netSales) * 100, 2) : 0.0;

        // 4. Breakdown Per Hari
        $dailyData = $this->getDailyBreakdown($businessId, $outletIds, $startDate, $endDate);

        // 5. Breakdown Kategori Produk
        $categoryData = $this->getCategoryBreakdown($businessId, $outletIds, $startDate, $endDate);

        return [
            'summary' => [
                'gross_sales' => $grossSales,
                'total_discounts' => $totalDiscounts,
                'net_sales' => $netSales,
                'total_tax' => $totalTax,
                'total_service_charge' => $totalServiceCharge,
                'transaction_count' => $transactionCount,
                'total_items_sold' => $totalItemsSold,
                'total_cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'gross_profit_margin_pct' => $grossProfitMargin,
                'waste_cost' => $wasteCost,
                'opname_deficit_cost' => $opnameDeficitCost,
                'opname_surplus_cost' => $opnameSurplusCost,
                'net_inventory_loss' => $netInventoryLoss,
                'operating_profit' => $operatingProfit,
                'operating_profit_margin_pct' => $operatingProfitMargin,
            ],
            'daily_breakdown' => $dailyData,
            'category_breakdown' => $categoryData,
        ];
    }

    /**
     * Dapatkan breakdown harian performa laba rugi.
     *
     * @param  array<string>  $outletIds
     * @return array<int, array<string, mixed>>
     */
    protected function getDailyBreakdown(string $businessId, array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'pgsql' => "to_char(transactions.created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', transactions.created_at)",
            default => 'DATE(transactions.created_at)',
        };

        $moveDateExpr = match ($driver) {
            'pgsql' => "to_char(inventory_movements.created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', inventory_movements.created_at)",
            default => 'DATE(inventory_movements.created_at)',
        };

        // Penjualan harian
        $dailySales = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('transactions.outlet_id', $outletIds))
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("$dateExpr as sale_date"),
                DB::raw('COALESCE(SUM(transactions.subtotal), 0) as gross_sales'),
                DB::raw('COALESCE(SUM(transactions.discount_amount), 0) as discount_amount'),
                DB::raw('COALESCE(SUM(transactions.total), 0) as net_sales')
            )
            ->groupByRaw($dateExpr)
            ->get()
            ->keyBy('sale_date');

        // COGS harian
        $dailyCogs = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('transactions.outlet_id', $outletIds))
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("$dateExpr as sale_date"),
                DB::raw('COALESCE(SUM(transaction_items.cogs_amount), 0) as cogs_amount')
            )
            ->groupByRaw($dateExpr)
            ->get()
            ->keyBy('sale_date');

        // Beban persediaan harian
        $dailyLosses = DB::table('inventory_movements')
            ->where('inventory_movements.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('inventory_movements.outlet_id', $outletIds))
            ->whereBetween('inventory_movements.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("$moveDateExpr as move_date"),
                DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.movement_type IN ('adjustment_out', 'opname_deficit') THEN inventory_movements.total_cost ELSE 0 END), 0) as total_losses")
            )
            ->groupByRaw($moveDateExpr)
            ->get()
            ->keyBy('move_date');

        // Satukan semua tanggal unik
        $allDates = $dailySales->keys()
            ->merge($dailyCogs->keys())
            ->merge($dailyLosses->keys())
            ->unique()
            ->sortDesc();

        $breakdown = [];
        foreach ($allDates as $date) {
            $sale = $dailySales[$date] ?? null;
            $cogs = $dailyCogs[$date] ?? null;
            $loss = $dailyLosses[$date] ?? null;

            $dayGross = (float) ($sale?->gross_sales ?? 0);
            $dayDiscount = (float) ($sale?->discount_amount ?? 0);
            $dayNet = (float) ($sale?->net_sales ?? 0);
            $dayCogs = (float) ($cogs?->cogs_amount ?? 0);
            $dayGrossProfit = $dayNet - $dayCogs;
            $dayGrossMargin = $dayNet > 0 ? round(($dayGrossProfit / $dayNet) * 100, 2) : 0.0;
            $dayLoss = (float) ($loss?->total_losses ?? 0);
            $dayOpProfit = $dayGrossProfit - $dayLoss;

            $breakdown[] = [
                'date' => (string) $date,
                'gross_sales' => $dayGross,
                'discount_amount' => $dayDiscount,
                'net_sales' => $dayNet,
                'cogs_amount' => $dayCogs,
                'gross_profit' => $dayGrossProfit,
                'gross_profit_margin_pct' => $dayGrossMargin,
                'inventory_losses' => $dayLoss,
                'operating_profit' => $dayOpProfit,
            ];
        }

        return $breakdown;
    }

    /**
     * Dapatkan breakdown profitabilitas per kategori produk.
     *
     * @param  array<string>  $outletIds
     * @return array<int, array<string, mixed>>
     */
    protected function getCategoryBreakdown(string $businessId, array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $categories = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->leftJoin('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('outlets.business_id', $businessId)
            ->where('products.business_id', $businessId)
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('transactions.outlet_id', $outletIds))
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                DB::raw("COALESCE(product_categories.name, 'Tanpa Kategori') as category_name"),
                DB::raw('COALESCE(SUM(transaction_items.qty), 0) as total_qty'),
                DB::raw('COALESCE(SUM(transaction_items.subtotal), 0) as total_sales'),
                DB::raw('COALESCE(SUM(transaction_items.cogs_amount), 0) as total_cogs')
            )
            ->groupBy(DB::raw("COALESCE(product_categories.name, 'Tanpa Kategori')"))
            ->orderBy(DB::raw('COALESCE(SUM(transaction_items.subtotal), 0)'), 'desc')
            ->get();

        $result = [];
        foreach ($categories as $cat) {
            $sales = (float) ($cat->total_sales ?? 0);
            $cogs = (float) ($cat->total_cogs ?? 0);
            $profit = $sales - $cogs;
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 2) : 0.0;

            $result[] = [
                'category_name' => (string) $cat->category_name,
                'total_qty' => (float) ($cat->total_qty ?? 0),
                'total_sales' => $sales,
                'total_cogs' => $cogs,
                'gross_profit' => $profit,
                'gross_profit_margin_pct' => $margin,
            ];
        }

        return $result;
    }
}
