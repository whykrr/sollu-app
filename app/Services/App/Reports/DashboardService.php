<?php

namespace App\Services\App\Reports;

use App\Models\Outlet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get complete dashboard overview dataset for Tenant App with caching.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $accessibleOutletIds
     * @return array<string, mixed>
     */
    public function getDashboardData(string $businessId, array $filters = [], array $accessibleOutletIds = []): array
    {
        $period = (string) ($filters['period'] ?? 'today');
        $outletId = ! empty($filters['outlet']) ? (string) $filters['outlet'] : null;
        $customStart = ! empty($filters['start_date']) ? (string) $filters['start_date'] : null;
        $customEnd = ! empty($filters['end_date']) ? (string) $filters['end_date'] : null;

        $accessibleKey = ! empty($accessibleOutletIds) ? implode(',', $accessibleOutletIds) : 'all';
        $cacheKey = "app:dashboard:{$businessId}:{$period}:".($outletId ?? 'all').':'.($customStart ?? 'none').':'.($customEnd ?? 'none').':'.$accessibleKey;

        return Cache::remember($cacheKey, 60, function () use ($businessId, $period, $outletId, $customStart, $customEnd, $accessibleOutletIds) {
            [$startDate, $endDate, $prevStartDate, $prevEndDate, $periodLabel] = $this->resolveDateRanges($period, $customStart, $customEnd);

            $outletIds = $this->resolveOutletIds($businessId, $outletId, $accessibleOutletIds);
            $isHourly = in_array($period, ['today', 'yesterday'], true) || ($startDate && $endDate && $startDate->isSameDay($endDate));

            $metrics = $this->getMetrics($businessId, $outletIds, $startDate, $endDate, $prevStartDate, $prevEndDate);
            $salesTrend = $this->getSalesTrend($businessId, $outletIds, $startDate, $endDate, $prevStartDate, $prevEndDate, $isHourly);
            $categorySalesTrend = $this->getCategorySalesTrend($businessId, $outletIds, $startDate, $endDate);
            $paymentMethodSummary = $this->getPaymentMethodSummary($businessId, $outletIds, $startDate, $endDate);
            $mostSoldProducts = $this->getMostSoldProducts($businessId, $outletIds, $startDate, $endDate);
            $lowStockProduct = $this->getLowStockProducts($businessId, $outletIds);
            $productNotSold = $this->getProductNotSold($businessId, $outletIds, $startDate, $endDate);

            return [
                'filters' => [
                    'period' => $period,
                    'outlet' => $outletId ?? '',
                    'start_date' => $startDate?->toDateString(),
                    'end_date' => $endDate?->toDateString(),
                    'period_label' => $periodLabel,
                ],
                'totalSales' => $metrics['totalSales'],
                'totalTransactions' => $metrics['totalTransactions'],
                'averageSales' => $metrics['averageSales'],
                'lowStockCount' => $metrics['lowStockCount'],
                'salesTrend' => $salesTrend,
                'categorySalesTrend' => $categorySalesTrend,
                'paymentMethodSummary' => $paymentMethodSummary,
                'mostSoldProducts' => $mostSoldProducts,
                'lowStockProduct' => $lowStockProduct,
                'productNotSold' => $productNotSold,
            ];
        });
    }

    /**
     * Get summary metrics for the dashboard with growth percentages.
     *
     * @param  array<string>  $outletIds
     * @return array<string, mixed>
     */
    public function getMetrics(
        string $businessId,
        array $outletIds,
        ?Carbon $startDate,
        ?Carbon $endDate,
        ?Carbon $prevStartDate,
        ?Carbon $prevEndDate
    ): array {
        $nowMetrics = $this->queryMetrics($businessId, $outletIds, $startDate, $endDate);
        $prevMetrics = ($prevStartDate && $prevEndDate)
            ? $this->queryMetrics($businessId, $outletIds, $prevStartDate, $prevEndDate)
            : (object) ['gross_sales' => 0, 'total_transactions' => 0];

        $nowGrossSales = (float) ($nowMetrics->gross_sales ?? 0);
        $prevGrossSales = (float) ($prevMetrics->gross_sales ?? 0);

        $nowTotalTx = (int) ($nowMetrics->total_transactions ?? 0);
        $prevTotalTx = (int) ($prevMetrics->total_transactions ?? 0);

        $nowAov = $nowTotalTx > 0 ? $nowGrossSales / $nowTotalTx : 0.0;
        $prevAov = $prevTotalTx > 0 ? $prevGrossSales / $prevTotalTx : 0.0;

        $lowStockCount = $this->queryLowStockCount($businessId, $outletIds);

        return [
            'totalSales' => [
                'now' => $nowGrossSales,
                'previous' => $prevGrossSales,
                'growth' => $this->calculateGrowth($nowGrossSales, $prevGrossSales),
            ],
            'totalTransactions' => [
                'now' => $nowTotalTx,
                'previous' => $prevTotalTx,
                'growth' => $this->calculateGrowth($nowTotalTx, $prevTotalTx),
            ],
            'averageSales' => [
                'now' => (float) round($nowAov, 2),
                'previous' => (float) round($prevAov, 2),
                'growth' => $this->calculateGrowth($nowAov, $prevAov),
            ],
            'lowStockCount' => $lowStockCount,
        ];
    }

    /**
     * Query gross sales and transaction count within a date range.
     *
     * @param  array<string>  $outletIds
     */
    private function queryMetrics(string $businessId, array $outletIds, ?Carbon $start, ?Carbon $end): object
    {
        $query = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed');

        if (! empty($outletIds)) {
            $query->whereIn('transactions.outlet_id', $outletIds);
        }

        if ($start && $end) {
            $query->whereBetween('transactions.created_at', [$start, $end]);
        }

        $result = $query
            ->selectRaw('COALESCE(SUM(transactions.subtotal), 0) as gross_sales, COUNT(transactions.id) as total_transactions')
            ->first();

        return $result ?: (object) ['gross_sales' => 0, 'total_transactions' => 0];
    }

    /**
     * Query total number of inventory items below or equal to minimum stock.
     *
     * @param  array<string>  $outletIds
     */
    private function queryLowStockCount(string $businessId, array $outletIds): int
    {
        $query = DB::table('inventory_balances')
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->where('inventory_items.business_id', $businessId);

        if (! empty($outletIds)) {
            $query->whereIn('inventory_balances.outlet_id', $outletIds);
        }

        return (int) $query
            ->groupBy('inventory_items.id', 'inventory_items.minimum_stock')
            ->havingRaw('SUM(inventory_balances.current_stock) <= inventory_items.minimum_stock')
            ->selectRaw('inventory_items.id')
            ->get()
            ->count();
    }

    /**
     * Get sales trend data for charting.
     *
     * @param  array<string>  $outletIds
     * @return array<string, mixed>
     */
    public function getSalesTrend(
        string $businessId,
        array $outletIds,
        ?Carbon $startDate,
        ?Carbon $endDate,
        ?Carbon $prevStartDate,
        ?Carbon $prevEndDate,
        bool $isHourly
    ): array {
        $nowTrend = ($startDate && $endDate)
            ? $this->queryTrend($businessId, $outletIds, $startDate, $endDate, $isHourly)
            : [];
        $prevTrend = ($prevStartDate && $prevEndDate)
            ? $this->queryTrend($businessId, $outletIds, $prevStartDate, $prevEndDate, $isHourly)
            : [];

        $labels = [];
        $nowData = [];
        $prevData = [];

        if ($isHourly) {
            for ($i = 0; $i < 24; $i++) {
                $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00';
                $labels[] = $label;
                $nowData[] = (float) ($nowTrend[$i] ?? 0);
                $prevData[] = (float) ($prevTrend[$i] ?? 0);
            }
        } elseif ($startDate && $endDate) {
            $periodDays = CarbonPeriod::create($startDate, $endDate);
            $prevCurrent = $prevStartDate ? $prevStartDate->copy() : null;

            foreach ($periodDays as $date) {
                $dateKeyNow = $date->format('Y-m-d');
                $dateKeyPrev = $prevCurrent ? $prevCurrent->format('Y-m-d') : null;

                $labels[] = $date->translatedFormat('d M');
                $nowData[] = (float) ($nowTrend[$dateKeyNow] ?? 0);
                $prevData[] = (float) ($dateKeyPrev && isset($prevTrend[$dateKeyPrev]) ? $prevTrend[$dateKeyPrev] : 0);

                if ($prevCurrent) {
                    $prevCurrent->addDay();
                }
            }
        }

        return [
            'label' => $labels,
            'value' => [
                ['title' => 'Periode Ini', 'data' => $nowData],
                ['title' => 'Periode Lalu', 'data' => $prevData],
            ],
        ];
    }

    /**
     * Query grouped sales trend per hour or per day.
     *
     * @param  array<string>  $outletIds
     * @return array<string|int, float>
     */
    private function queryTrend(string $businessId, array $outletIds, Carbon $start, Carbon $end, bool $isHourly): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $groupExpr = $isHourly ? "CAST(strftime('%H', transactions.created_at) AS INTEGER)" : 'date(transactions.created_at)';
        } elseif ($driver === 'pgsql') {
            $groupExpr = $isHourly ? 'EXTRACT(HOUR FROM transactions.created_at)' : "to_char(transactions.created_at, 'YYYY-MM-DD')";
        } else {
            $groupExpr = $isHourly ? 'HOUR(transactions.created_at)' : 'DATE(transactions.created_at)';
        }

        $selectExpr = $isHourly ? "$groupExpr as time_key" : "$groupExpr as date_key";

        $results = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw("$selectExpr, SUM(transactions.subtotal) as total")
            ->groupByRaw($groupExpr)
            ->get();

        $trend = [];
        foreach ($results as $row) {
            if ($isHourly) {
                $trend[(int) $row->time_key] = (float) $row->total;
            } else {
                $trend[$row->date_key] = (float) $row->total;
            }
        }

        return $trend;
    }

    /**
     * Get Sales Category Trend (Top 5 categories).
     *
     * @param  array<string>  $outletIds
     * @return array{label: array<string>, value: array<float>}
     */
    public function getCategorySalesTrend(string $businessId, array $outletIds, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed');

        if (! empty($outletIds)) {
            $query->whereIn('transactions.outlet_id', $outletIds);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
        }

        $results = $query
            ->selectRaw("COALESCE(product_categories.name, 'Tanpa Kategori') as category_name, SUM(transaction_items.subtotal) as total_sales")
            ->groupBy('category_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $labels = [];
        $values = [];
        foreach ($results as $row) {
            $labels[] = $row->category_name;
            $values[] = (float) $row->total_sales;
        }

        if (empty($labels)) {
            return [
                'label' => ['Belum Ada Data'],
                'value' => [0.0],
            ];
        }

        return [
            'label' => $labels,
            'value' => $values,
        ];
    }

    /**
     * Get Payment Method Summary.
     *
     * @param  array<string>  $outletIds
     * @return array{label: array<string>, value: array<int>, revenue: array<float>}
     */
    public function getPaymentMethodSummary(string $businessId, array $outletIds, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed');

        if (! empty($outletIds)) {
            $query->whereIn('transactions.outlet_id', $outletIds);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
        }

        $results = $query
            ->selectRaw('payment_methods.name, COUNT(transaction_payments.id) as total_tx, SUM(transaction_payments.amount) as total_rev')
            ->groupBy('payment_methods.name')
            ->orderByDesc('total_tx')
            ->get();

        $totalCount = $results->sum('total_tx');

        $labels = [];
        $percentages = [];
        $revenues = [];
        foreach ($results as $row) {
            $txCount = (int) $row->total_tx;
            $percentage = $totalCount > 0 ? (int) round(($txCount / $totalCount) * 100) : 0;

            $labels[] = $row->name;
            $percentages[] = $percentage;
            $revenues[] = (float) $row->total_rev;
        }

        return [
            'label' => $labels,
            'value' => $percentages,
            'revenue' => $revenues,
        ];
    }

    /**
     * Get Top 5 Most Sold Products.
     *
     * @param  array<string>  $outletIds
     * @return array<int, array{name: string, total: int, revenue: float}>
     */
    public function getMostSoldProducts(string $businessId, array $outletIds, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed');

        if (! empty($outletIds)) {
            $query->whereIn('transactions.outlet_id', $outletIds);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
        }

        $results = $query
            ->selectRaw('products.name, SUM(transaction_items.qty) as total, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return array_map(function ($row) {
            return [
                'name' => $row->name,
                'total' => (int) $row->total,
                'revenue' => (float) $row->revenue,
            ];
        }, $results->toArray());
    }

    /**
     * Get Top 5 Low Stock Products.
     *
     * @param  array<string>  $outletIds
     * @return array<int, array{name: string, stock: int, min_stock: int}>
     */
    public function getLowStockProducts(string $businessId, array $outletIds): array
    {
        $query = DB::table('inventory_balances')
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->join('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
            ->where('inventory_items.business_id', $businessId);

        if (! empty($outletIds)) {
            $query->whereIn('inventory_balances.outlet_id', $outletIds);
        }

        $results = $query
            ->selectRaw('product_items.name, SUM(inventory_balances.current_stock) as stock, inventory_items.minimum_stock')
            ->groupBy('inventory_items.id', 'product_items.name', 'inventory_items.minimum_stock')
            ->havingRaw('SUM(inventory_balances.current_stock) <= inventory_items.minimum_stock')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        return array_map(function ($row) {
            return [
                'name' => $row->name,
                'stock' => (int) $row->stock,
                'min_stock' => (int) $row->minimum_stock,
            ];
        }, $results->toArray());
    }

    /**
     * Get Top 5 Products Not Sold in the period.
     *
     * @param  array<string>  $outletIds
     * @return array<int, array{name: string}>
     */
    public function getProductNotSold(string $businessId, array $outletIds, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $results = DB::table('products')
            ->where('products.business_id', $businessId)
            ->whereNull('products.deleted_at')
            ->whereNotExists(function ($query) use ($businessId, $outletIds, $startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('transaction_items')
                    ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                    ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                    ->where('outlets.business_id', $businessId)
                    ->whereColumn('transaction_items.product_id', 'products.id')
                    ->where('transactions.status', 'completed')
                    ->when(! empty($outletIds), function ($subQuery) use ($outletIds) {
                        $subQuery->whereIn('transactions.outlet_id', $outletIds);
                    })
                    ->when($startDate && $endDate, function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->whereBetween('transactions.created_at', [$startDate, $endDate]);
                    });
            })
            ->select('products.name')
            ->limit(5)
            ->get();

        return array_map(function ($row) {
            return ['name' => $row->name];
        }, $results->toArray());
    }

    /**
     * Resolve start and end dates based on period preset.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?Carbon, 3: ?Carbon, 4: string}
     */
    public function resolveDateRanges(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        return match ($period) {
            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                now()->subDays(2)->startOfDay(),
                now()->subDays(2)->endOfDay(),
                'Kemarin',
            ],
            '7_days', 'last_7_days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                now()->subDays(13)->startOfDay(),
                now()->subDays(7)->endOfDay(),
                '7 Hari Terakhir',
            ],
            'last_30_days' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                now()->subDays(59)->startOfDay(),
                now()->subDays(30)->endOfDay(),
                '30 Hari Terakhir',
            ],
            'this_month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Bulan Ini',
            ],
            'last_month' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                now()->subMonths(2)->startOfMonth(),
                now()->subMonths(2)->endOfMonth(),
                'Bulan Lalu',
            ],
            'this_year' => [
                now()->startOfYear(),
                now()->endOfYear(),
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
                'Tahun Ini',
            ],
            'all_time' => [
                null,
                null,
                null,
                null,
                'Sepanjang Waktu',
            ],
            'custom' => $this->resolveCustomRange($customStart, $customEnd),
            default => [
                now()->startOfDay(),
                now()->endOfDay(),
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                'Hari Ini',
            ],
        };
    }

    /**
     * Resolve custom date range.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?Carbon, 3: ?Carbon, 4: string}
     */
    protected function resolveCustomRange(?string $customStart, ?string $customEnd): array
    {
        $start = $customStart ? Carbon::parse($customStart)->startOfDay() : now()->startOfDay();
        $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : now()->endOfDay();

        $diffDays = max(1, (int) $start->diffInDays($end) + 1);
        $prevStart = $start->copy()->subDays($diffDays);
        $prevEnd = $start->copy()->subDay()->endOfDay();

        $label = $start->translatedFormat('d M Y').' - '.$end->translatedFormat('d M Y');

        return [$start, $end, $prevStart, $prevEnd, $label];
    }

    /**
     * Resolve outlet IDs array scoped to business and user accessibility.
     *
     * @param  array<string>  $accessibleOutletIds
     * @return array<string>
     */
    protected function resolveOutletIds(string $businessId, ?string $outletId, array $accessibleOutletIds = []): array
    {
        if (! empty($outletId)) {
            $query = Outlet::query()
                ->where('business_id', $businessId)
                ->where('id', $outletId);

            if (! empty($accessibleOutletIds)) {
                $query->whereIn('id', $accessibleOutletIds);
            }

            $exists = $query->exists();

            return $exists ? [$outletId] : [];
        }

        return $accessibleOutletIds;
    }

    /**
     * Calculate growth percentage between current and previous values.
     */
    protected function calculateGrowth(float|int $current, float|int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (float) round((($current - $previous) / $previous) * 100, 1);
    }
}
