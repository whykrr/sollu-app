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
            ->when(!empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('shifts.outlet_id', $outletIds);
            })
            ->whereBetween('shifts.opened_at', [$startDate, $endDate])
            ->select(
                'shifts.id',
                'shifts.opened_at',
                'shifts.closed_at',
                'users.name as cashier_name',
                'shifts.starting_cash',
                'shifts.expected_ending_cash',
                'shifts.actual_ending_cash',
                'shifts.status',
                DB::raw('(shifts.actual_ending_cash - shifts.expected_ending_cash) as difference')
            )
            ->orderBy('shifts.opened_at', 'desc')
            ->get();

        return $shifts;
    }
}
