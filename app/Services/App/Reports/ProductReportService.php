<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductReportService
{
    /**
     * Get complete product sales report with summary KPIs and paginated list.
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

        return [
            'summary' => $this->getSummary($businessId, $outletIds, $startDate, $endDate),
            'products' => $this->getPaginatedReport($businessId, $outletIds, $startDate, $endDate, $filters),
        ];
    }

    /**
     * Get aggregated KPI summary for product sales.
     *
     * @param  array<string>  $outletIds
     * @return array<string, float|int>
     */
    public function getSummary(
        string $businessId,
        array $outletIds,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $outletIds = array_values(array_filter($outletIds));

        $result = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('outlets.business_id', $businessId)
            ->where('products.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw('
                COALESCE(SUM(transaction_items.qty), 0) as total_qty,
                COALESCE(SUM(transaction_items.subtotal), 0) as total_sales,
                COUNT(DISTINCT transaction_items.product_id) as total_products
            ')
            ->first();

        return [
            'total_qty' => (float) ($result->total_qty ?? 0),
            'total_sales' => (float) ($result->total_sales ?? 0),
            'total_products' => (int) ($result->total_products ?? 0),
        ];
    }

    /**
     * Get paginated product performance list.
     *
     * @param  array<string>  $outletIds
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedReport(
        string $businessId,
        array $outletIds,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): LengthAwarePaginator {
        $outletIds = array_values(array_filter($outletIds));
        $perPage = (int) ($filters['perpage'] ?? 15);

        $paginator = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('outlets.business_id', $businessId)
            ->where('products.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw("
                products.id as product_id,
                products.name as product_name,
                COALESCE(product_categories.name, 'Tanpa Kategori') as category_name,
                COALESCE(SUM(transaction_items.qty), 0) as total_qty,
                COALESCE(SUM(transaction_items.subtotal), 0) as total_sales
            ")
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_qty')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return (object) [
                'product_id' => (string) $item->product_id,
                'product_name' => (string) $item->product_name,
                'category_name' => (string) $item->category_name,
                'total_qty' => (float) $item->total_qty,
                'total_sales' => (float) $item->total_sales,
            ];
        });

        return $paginator;
    }
}
