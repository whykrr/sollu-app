<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerReportService
{
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        $customers = DB::table('transactions')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.email',
                DB::raw('COUNT(transactions.id) as total_visits'),
                DB::raw('SUM(transactions.total) as total_spent'),
                DB::raw('MAX(transactions.created_at) as last_visit')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderBy('total_spent', 'desc')
            ->get();

        return $customers;
    }
}
