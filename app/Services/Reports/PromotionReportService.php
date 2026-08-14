<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionReportService
{
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        $promotions = DB::table('transaction_promos')
            ->join('transactions', 'transaction_promos.transaction_id', '=', 'transactions.id')
            ->join('promos', 'transaction_promos.promo_id', '=', 'promos.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                'promos.name as promo_name',
                'promos.promo_type',
                DB::raw('COUNT(transaction_promos.id) as total_usage'),
                DB::raw('SUM(transaction_promos.discount_amount) as total_discount_given')
            )
            ->groupBy('promos.id', 'promos.name', 'promos.promo_type')
            ->orderBy('total_usage', 'desc')
            ->get();

        return $promotions;
    }
}
