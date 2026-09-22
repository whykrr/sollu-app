<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Get complete sales report dataset with summary KPIs and paginated detail.
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
        $perPage = (int) ($filters['perpage'] ?? 15);

        return [
            'summary' => $this->getSummary($businessId, $outletIds, $startDate, $endDate),
            'daily_sales' => $this->getDailySalesPaginated($businessId, $outletIds, $startDate, $endDate, $perPage),
            'payment_methods' => $this->getPaymentMethodsSummary($businessId, $outletIds, $startDate, $endDate),
        ];
    }

    /**
     * Get aggregated KPI summary for the given period and outlets.
     *
     * @param  array<string>  $outletIds
     * @return array<string, float|int>
     */
    public function getSummary(string $businessId, array $outletIds, Carbon $startDate, Carbon $endDate): array
    {
        $outletIds = array_values(array_filter($outletIds));

        $result = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw('
                COALESCE(SUM(transactions.subtotal), 0) as gross_sales,
                COALESCE(SUM(transactions.discount_amount), 0) as total_discount,
                COALESCE(SUM(transactions.tax_amount), 0) as total_tax,
                COALESCE(SUM(transactions.service_charge_amount), 0) as total_service_charge,
                COALESCE(SUM(transactions.total), 0) as net_sales,
                COUNT(transactions.id) as total_transactions
            ')
            ->first();

        $grossSales = (float) ($result->gross_sales ?? 0);
        $totalDiscount = (float) ($result->total_discount ?? 0);
        $totalTax = (float) ($result->total_tax ?? 0);
        $totalServiceCharge = (float) ($result->total_service_charge ?? 0);
        $netSales = (float) ($result->net_sales ?? 0);
        $totalTransactions = (int) ($result->total_transactions ?? 0);
        $avgTransaction = $totalTransactions > 0 ? round($netSales / $totalTransactions, 2) : 0.0;

        return [
            'gross_sales' => $grossSales,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_service_charge' => $totalServiceCharge,
            'net_sales' => $netSales,
            'total_transactions' => $totalTransactions,
            'average_transaction' => (float) $avgTransaction,
        ];
    }

    /**
     * Get paginated daily sales breakdown.
     *
     * @param  array<string>  $outletIds
     */
    public function getDailySalesPaginated(
        string $businessId,
        array $outletIds,
        Carbon $startDate,
        Carbon $endDate,
        int $perPage = 15
    ): LengthAwarePaginator {
        $outletIds = array_values(array_filter($outletIds));

        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'pgsql' => "to_char(transactions.created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', transactions.created_at)",
            default => 'DATE(transactions.created_at)',
        };

        $paginator = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw("
                $dateExpr as date,
                COALESCE(SUM(transactions.subtotal), 0) as gross_sales,
                COALESCE(SUM(transactions.discount_amount), 0) as total_discount,
                COALESCE(SUM(transactions.tax_amount), 0) as total_tax,
                COALESCE(SUM(transactions.total), 0) as net_sales,
                COUNT(transactions.id) as transaction_count
            ")
            ->groupByRaw($dateExpr)
            ->orderByRaw("$dateExpr DESC")
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return (object) [
                'date' => (string) $item->date,
                'gross_sales' => (float) $item->gross_sales,
                'total_discount' => (float) $item->total_discount,
                'total_tax' => (float) $item->total_tax,
                'net_sales' => (float) $item->net_sales,
                'transaction_count' => (int) $item->transaction_count,
            ];
        });

        return $paginator;
    }

    /**
     * Get payment methods summary breakdown.
     *
     * @param  array<string>  $outletIds
     * @return Collection<int, object>
     */
    public function getPaymentMethodsSummary(
        string $businessId,
        array $outletIds,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $outletIds = array_values(array_filter($outletIds));

        $results = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('outlets.business_id', $businessId)
            ->where('payment_methods.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw('
                payment_methods.id as payment_id,
                payment_methods.name as payment_name,
                COUNT(transaction_payments.id) as total_transactions,
                COALESCE(SUM(transaction_payments.amount), 0) as total_revenue
            ')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total_transactions')
            ->get();

        return $results->map(function ($row) {
            return (object) [
                'payment_id' => (string) $row->payment_id,
                'payment_name' => (string) $row->payment_name,
                'total_transactions' => (int) $row->total_transactions,
                'total_revenue' => (float) $row->total_revenue,
            ];
        });
    }
}
