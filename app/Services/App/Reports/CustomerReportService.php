<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerReportService
{
    /**
     * Dapatkan Laporan Pelanggan lengkap dengan ringkasan KPI dan data terpaginasi.
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
            'customers' => $this->getPaginatedReport($businessId, $outletIds, $startDate, $endDate, $filters),
        ];
    }

    /**
     * Dapatkan ringkasan KPI kontribusi pelanggan.
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

        $result = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('outlets.business_id', $businessId)
            ->where('customers.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw('
                COUNT(transactions.id) as total_customer_visits,
                COALESCE(SUM(transactions.total), 0) as total_customer_spent,
                COUNT(DISTINCT customers.id) as total_unique_customers
            ')
            ->first();

        $totalSpent = (float) ($result?->total_customer_spent ?? 0);
        $totalVisits = (int) ($result?->total_customer_visits ?? 0);
        $avgSpentPerVisit = $totalVisits > 0 ? round($totalSpent / $totalVisits, 2) : 0.0;

        return [
            'total_customer_visits' => $totalVisits,
            'total_customer_spent' => $totalSpent,
            'total_unique_customers' => (int) ($result?->total_unique_customers ?? 0),
            'average_spent_per_visit' => (float) $avgSpentPerVisit,
        ];
    }

    /**
     * Dapatkan data daftar pelanggan terpaginasi berdasarkan total belanja.
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
        $search = $filters['search'] ?? null;

        $paginator = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('outlets.business_id', $businessId)
            ->where('customers.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('customers.name', 'ilike', "%{$search}%")
                        ->orWhere('customers.phone', 'ilike', "%{$search}%")
                        ->orWhere('customers.email', 'ilike', "%{$search}%");
                });
            })
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.email',
                DB::raw('COUNT(transactions.id) as total_visits'),
                DB::raw('COALESCE(SUM(transactions.total), 0) as total_spent'),
                DB::raw('MAX(transactions.created_at) as last_visit')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderBy('total_spent', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return (object) [
                'id' => (string) $item->id,
                'name' => (string) $item->name,
                'phone' => $item->phone ? (string) $item->phone : null,
                'email' => $item->email ? (string) $item->email : null,
                'total_visits' => (int) $item->total_visits,
                'total_spent' => (float) $item->total_spent,
                'last_visit' => $item->last_visit ? (string) $item->last_visit : null,
            ];
        });

        return $paginator;
    }
}
