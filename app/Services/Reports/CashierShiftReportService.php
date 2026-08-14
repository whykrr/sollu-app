<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashierShiftReportService
{
    public function getReport(string|array $outletId, Carbon $startDate, Carbon $endDate)
    {
        $outletIds = array_filter((array) $outletId);

        $shifts = DB::table('shifts')
            ->join('users', 'shifts.user_id', '=', 'users.id')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('shifts.outlet_id', $outletIds);
            })
            ->whereBetween('shifts.created_at', [$startDate, $endDate])
            ->select(
                'shifts.id',
                'shifts.created_at as opened_at',
                'shifts.closed_at',
                'users.name as cashier_name',
                'shifts.opening_cash as starting_cash',
                'shifts.expected_cash as expected_ending_cash',
                'shifts.closing_cash as actual_ending_cash',
                'shifts.status',
                DB::raw('(COALESCE(shifts.closing_cash, 0) - COALESCE(shifts.expected_cash, 0)) as difference')
            )
            ->orderBy('shifts.created_at', 'desc')
            ->paginate(15);

        return $shifts;
    }
}
