<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        // Omset per hari
        $dailySales = DB::table('transactions')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('outlet_id', $outletIds);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(subtotal) as gross_sales'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(total) as net_sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->paginate(15);

        // Breakdown Pembayaran
        $paymentMethods = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->select(
                'payment_methods.name as payment_name',
                DB::raw('COUNT(transaction_payments.id) as total_transactions'),
                DB::raw('SUM(transaction_payments.amount) as total_revenue')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        return [
            'daily_sales'     => $dailySales,
            'payment_methods' => $paymentMethods,
        ];
    }
}
