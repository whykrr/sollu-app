<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportCashierReportJob extends AbstractCsvExportJob
{
    public function __construct(
        User $user,
        public array $outletIds,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        parent::__construct($user);
        $this->outletIds = array_filter($this->outletIds);
    }

    protected function getQuery()
    {
        return DB::table('shifts')
            ->join('users', 'shifts.user_id', '=', 'users.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('shifts.outlet_id', $this->outletIds);
            })
            ->whereBetween('shifts.created_at', [$this->startDate, $this->endDate])
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
            ->orderBy('shifts.created_at', 'desc');
    }

    protected function getHeaders(): array
    {
        return [
            'Buka',
            'Tutup',
            'Kasir',
            'Kas Awal',
            'Sistem (Expected)',
            'Fisik (Actual)',
            'Selisih',
        ];
    }

    protected function mapRow($row): array
    {
        return [
            $row->opened_at ? Carbon::parse($row->opened_at)->format('Y-m-d H:i:s') : '-',
            $row->closed_at ? Carbon::parse($row->closed_at)->format('Y-m-d H:i:s') : 'Belum Tutup',
            $row->cashier_name ?? '-',
            (float) $row->starting_cash,
            (float) $row->expected_ending_cash,
            (float) $row->actual_ending_cash,
            (float) $row->difference,
        ];
    }

    protected function getModuleName(): string
    {
        return 'Laporan Kasir';
    }

    protected function getFileName(): string
    {
        return 'cashiers_export_'.time().'.csv';
    }
}
