<?php

namespace App\Services\App\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CashierShiftReportService
{
    /**
     * Dapatkan Laporan Shift Kasir lengkap dengan ringkasan KPI dan data terpaginasi.
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
            'shifts' => $this->getPaginatedReport($businessId, $outletIds, $startDate, $endDate, $filters),
        ];
    }

    /**
     * Dapatkan ringkasan KPI performa shift kasir.
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

        $result = DB::table('shifts')
            ->join('outlets', 'shifts.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $businessId)
            ->whereBetween('shifts.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('shifts.outlet_id', $outletIds);
            })
            ->selectRaw('
                COUNT(shifts.id) as total_shifts,
                COALESCE(SUM(shifts.opening_cash), 0) as total_opening_cash,
                COALESCE(SUM(shifts.expected_cash), 0) as total_expected_cash,
                COALESCE(SUM(shifts.closing_cash), 0) as total_closing_cash,
                COALESCE(SUM(shifts.total_sales), 0) as total_shift_sales,
                COALESCE(SUM(COALESCE(shifts.closing_cash, 0) - COALESCE(shifts.expected_cash, 0)), 0) as total_difference
            ')
            ->first();

        return [
            'total_shifts' => (int) ($result?->total_shifts ?? 0),
            'total_opening_cash' => (float) ($result?->total_opening_cash ?? 0),
            'total_expected_cash' => (float) ($result?->total_expected_cash ?? 0),
            'total_closing_cash' => (float) ($result?->total_closing_cash ?? 0),
            'total_shift_sales' => (float) ($result?->total_shift_sales ?? 0),
            'total_difference' => (float) ($result?->total_difference ?? 0),
        ];
    }

    /**
     * Dapatkan data daftar shift terpaginasi.
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

        $paginator = DB::table('shifts')
            ->join('outlets', 'shifts.outlet_id', '=', 'outlets.id')
            ->join('users', 'shifts.user_id', '=', 'users.id')
            ->where('outlets.business_id', $businessId)
            ->whereBetween('shifts.created_at', [$startDate, $endDate])
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('shifts.outlet_id', $outletIds);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('users.name', 'ilike', "%{$search}%")
                        ->orWhere('shifts.shift_number', 'ilike', "%{$search}%");
                });
            })
            ->select(
                'shifts.id',
                'shifts.shift_number',
                'shifts.created_at as opened_at',
                'shifts.closed_at',
                'users.name as cashier_name',
                'outlets.name as outlet_name',
                'shifts.opening_cash as starting_cash',
                'shifts.expected_cash as expected_ending_cash',
                'shifts.closing_cash as actual_ending_cash',
                'shifts.total_sales',
                'shifts.status',
                DB::raw('(COALESCE(shifts.closing_cash, 0) - COALESCE(shifts.expected_cash, 0)) as difference')
            )
            ->orderBy('shifts.created_at', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return (object) [
                'id' => (string) $item->id,
                'shift_number' => (string) ($item->shift_number ?? ''),
                'opened_at' => (string) $item->opened_at,
                'closed_at' => $item->closed_at ? (string) $item->closed_at : null,
                'cashier_name' => (string) $item->cashier_name,
                'outlet_name' => (string) $item->outlet_name,
                'starting_cash' => (float) $item->starting_cash,
                'expected_ending_cash' => (float) $item->expected_ending_cash,
                'actual_ending_cash' => (float) $item->actual_ending_cash,
                'total_sales' => (float) ($item->total_sales ?? 0),
                'status' => (string) $item->status,
                'difference' => (float) $item->difference,
            ];
        });

        return $paginator;
    }
}
