<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PromotionReportService
{
    /**
     * Dapatkan Laporan Promosi lengkap dengan ringkasan KPI dan data terpaginasi.
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
            'promotions' => $this->getPaginatedReport($businessId, $outletIds, $startDate, $endDate, $filters),
        ];
    }

    /**
     * Dapatkan ringkasan KPI penggunaan promosi.
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

        $result = DB::table('transaction_promos')
            ->join('transactions', 'transaction_promos.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('promos', 'transaction_promos.promo_id', '=', 'promos.id')
            ->where('outlets.business_id', $businessId)
            ->where('promos.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->selectRaw('
                COUNT(transaction_promos.id) as total_usage,
                COALESCE(SUM(transaction_promos.discount_amount), 0) as total_discount_given,
                COUNT(DISTINCT promos.id) as total_active_promos
            ')
            ->first();

        return [
            'total_usage' => (int) ($result?->total_usage ?? 0),
            'total_discount_given' => (float) ($result?->total_discount_given ?? 0),
            'total_active_promos' => (int) ($result?->total_active_promos ?? 0),
        ];
    }

    /**
     * Dapatkan data daftar promosi terpaginasi.
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

        $paginator = DB::table('transaction_promos')
            ->join('transactions', 'transaction_promos.transaction_id', '=', 'transactions.id')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('promos', 'transaction_promos.promo_id', '=', 'promos.id')
            ->where('outlets.business_id', $businessId)
            ->where('promos.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('transactions.outlet_id', $outletIds);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('promos.name', 'ilike', "%{$search}%");
            })
            ->select(
                'promos.id as promo_id',
                'promos.name as promo_name',
                'promos.promo_type',
                DB::raw('COUNT(transaction_promos.id) as total_usage'),
                DB::raw('COALESCE(SUM(transaction_promos.discount_amount), 0) as total_discount_given')
            )
            ->groupBy('promos.id', 'promos.name', 'promos.promo_type')
            ->orderBy('total_usage', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return (object) [
                'promo_id' => (string) $item->promo_id,
                'promo_name' => (string) $item->promo_name,
                'promo_type' => (string) $item->promo_type,
                'total_usage' => (int) $item->total_usage,
                'total_discount_given' => (float) $item->total_discount_given,
            ];
        });

        return $paginator;
    }
}
