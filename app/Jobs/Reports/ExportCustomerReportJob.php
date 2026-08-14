<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportCustomerReportJob extends AbstractCsvExportJob
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
        return DB::table('transactions')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
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
            ->orderBy('total_spent', 'desc');
    }

    protected function getHeaders(): array
    {
        return [
            'Nama Pelanggan',
            'Telepon',
            'Email',
            'Total Kunjungan',
            'Total Belanja',
            'Kunjungan Terakhir',
        ];
    }

    protected function mapRow($row): array
    {
        return [
            $row->name ?? '-',
            $row->phone ?? '-',
            $row->email ?? '-',
            (int) $row->total_visits,
            (float) $row->total_spent,
            $row->last_visit ? Carbon::parse($row->last_visit)->format('Y-m-d H:i:s') : '-',
        ];
    }

    protected function getModuleName(): string
    {
        return 'Laporan Pelanggan';
    }

    protected function getFileName(): string
    {
        return 'customers_export_'.time().'.csv';
    }
}
