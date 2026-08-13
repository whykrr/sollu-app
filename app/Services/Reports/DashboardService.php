<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get summary metrics for the dashboard.
     */
    public function getMetrics(string|array $outletId, Carbon $startDate, Carbon $endDate, Carbon $prevStartDate, Carbon $prevEndDate): array
    {
        $outletIds = array_filter((array) $outletId);

        $nowMetrics = $this->queryMetrics($outletIds, $startDate, $endDate);
        $prevMetrics = $this->queryMetrics($outletIds, $prevStartDate, $prevEndDate);

        return [
            'totalSales' => [
                'now' => (float) $nowMetrics->gross_sales,
                'previous' => (float) $prevMetrics->gross_sales,
            ],
            'totalTransactions' => [
                'now' => (int) $nowMetrics->total_transactions,
                'previous' => (int) $prevMetrics->total_transactions,
            ],
            'averageSales' => [
                'now' => $nowMetrics->total_transactions > 0 ? (float) $nowMetrics->gross_sales / $nowMetrics->total_transactions : 0,
                'previous' => $prevMetrics->total_transactions > 0 ? (float) $prevMetrics->gross_sales / $prevMetrics->total_transactions : 0,
            ],
        ];
    }

    private function queryMetrics(array $outletIds, Carbon $start, Carbon $end)
    {
        return DB::table('transactions')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('outlet_id', $outletIds);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as gross_sales, COUNT(id) as total_transactions')
            ->first();
    }

    /**
     * Get sales trend data for charting.
     */
    public function getSalesTrend(array $outletIds, Carbon $startDate, Carbon $endDate, Carbon $prevStartDate, Carbon $prevEndDate, bool $isToday): array
    {
        $nowTrend = $this->queryTrend($outletIds, $startDate, $endDate, $isToday);
        $prevTrend = $this->queryTrend($outletIds, $prevStartDate, $prevEndDate, $isToday);

        // Build labels (all hours for today, or all dates for range)
        $labels = [];
        $nowData = [];
        $prevData = [];

        if ($isToday) {
            // Hours from 0 to 23
            for ($i = 0; $i < 24; $i++) {
                $label = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $labels[] = $label;
                $nowData[] = $nowTrend[$i] ?? 0;
                $prevData[] = $prevTrend[$i] ?? 0;
            }
        } else {
            $current = $startDate->copy();
            $interval = $current->diffInDays($endDate);
            $prevCurrent = $prevStartDate->copy();

            for ($i = 0; $i <= $interval; $i++) {
                $dateKeyNow = $current->format('Y-m-d');
                $dateKeyPrev = $prevCurrent->format('Y-m-d');
                
                $labels[] = $current->format('d M');
                $nowData[] = $nowTrend[$dateKeyNow] ?? 0;
                $prevData[] = $prevTrend[$dateKeyPrev] ?? 0;

                $current->addDay();
                $prevCurrent->addDay();
            }
        }

        return [
            'label' => $labels,
            'value' => [
                ['title' => 'Periode Ini', 'data' => $nowData],
                ['title' => 'Periode Lalu', 'data' => $prevData],
            ]
        ];
    }

    private function queryTrend(array $outletIds, Carbon $start, Carbon $end, bool $isToday): array
    {
        $groupExpr = $isToday ? 'EXTRACT(HOUR FROM created_at)' : 'DATE(created_at)';
        $selectExpr = $isToday ? "$groupExpr as time_key" : "$groupExpr as date_key";
        
        $results = DB::table('transactions')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('outlet_id', $outletIds);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("$selectExpr, SUM(subtotal) as total")
            ->groupByRaw($groupExpr)
            ->get();

        $trend = [];
        foreach ($results as $row) {
            if ($isToday) {
                $trend[(int)$row->time_key] = (float)$row->total;
            } else {
                $trend[$row->date_key] = (float)$row->total;
            }
        }

        return $trend;
    }

    /**
     * Get Sales Category Trend
     */
    public function getCategorySalesTrend(array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $results = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(product_categories.name, \'Tanpa Kategori\') as category_name, SUM(transaction_items.subtotal) as total_sales')
            ->groupBy('category_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $labels = [];
        $values = [];
        foreach ($results as $row) {
            $labels[] = $row->category_name;
            $values[] = (float)$row->total_sales;
        }

        return [
            'label' => $labels,
            'value' => $values,
        ];
    }

    /**
     * Get Payment Method Summary
     */
    public function getPaymentMethodSummary(array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $results = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->selectRaw('payment_methods.name, COUNT(transaction_payments.id) as total_tx, SUM(transaction_payments.amount) as total_rev')
            ->groupBy('payment_methods.name')
            ->orderByDesc('total_tx')
            ->get();

        $labels = [];
        $values = [];
        $revenues = [];
        foreach ($results as $row) {
            $labels[] = $row->name;
            $values[] = (int)$row->total_tx;
            $revenues[] = (float)$row->total_rev;
        }

        return [
            'label' => $labels,
            'value' => $values,
            'revenue' => $revenues,
        ];
    }

    /**
     * Get Most Sold Products
     */
    public function getMostSoldProducts(array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $results = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->selectRaw('products.name, SUM(transaction_items.qty) as total, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return array_map(function($row) {
            return [
                'name' => $row->name,
                'total' => (int)$row->total,
                'revenue' => (float)$row->revenue,
            ];
        }, $results->toArray());
    }

    /**
     * Get Low Stock Products
     */
    public function getLowStockProducts(array $outletIds): array
    {
        $results = DB::table('inventory_balances')
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('inventory_balances.outlet_id', $outletIds);
            })
            ->selectRaw('inventory_items.name, SUM(inventory_balances.current_stock) as stock, inventory_items.minimum_stock')
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.minimum_stock')
            ->havingRaw('SUM(inventory_balances.current_stock) <= inventory_items.minimum_stock')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        return array_map(function($row) {
            return [
                'name' => $row->name,
                'stock' => (int)$row->stock,
                'min_stock' => (int)$row->minimum_stock,
            ];
        }, $results->toArray());
    }

    /**
     * Get Product Not Sold
     */
    public function getProductNotSold(array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $results = DB::table('products')
            ->whereNotExists(function ($query) use ($outletIds, $startDate, $endDate) {
                $query->select(DB::raw(1))
                      ->from('transaction_items')
                      ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                      ->whereColumn('transaction_items.product_id', 'products.id')
                      ->whereBetween('transactions.created_at', [$startDate, $endDate])
                      ->where('transactions.status', 'completed')
                      ->when(!empty($outletIds), function ($subQuery) use ($outletIds) {
                          $subQuery->whereIn('transactions.outlet_id', $outletIds);
                      });
            })
            ->select('name')
            ->limit(5)
            ->get();

        return array_map(function($row) {
            return ['name' => $row->name];
        }, $results->toArray());
    }
}
