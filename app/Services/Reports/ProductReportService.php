<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductReportService
{
    /**
     * Dapatkan Laporan Produk (tanpa Margin/HPP).
     */
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                'products.name as product_name',
                'product_categories.name as category_name',
                DB::raw('SUM(transaction_items.qty) as total_qty'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_qty')
            ->get();
    }
}
