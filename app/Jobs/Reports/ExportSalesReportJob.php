<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportSalesReportJob extends AbstractCsvExportJob
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
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
            ->select(
                DB::raw('DATE(transactions.created_at) as date'),
                DB::raw('SUM(transactions.subtotal) as gross_sales'),
                DB::raw('SUM(transactions.discount_amount) as total_discount'),
                DB::raw('SUM(transactions.tax_amount) as total_tax'),
                DB::raw('SUM(transactions.total) as net_sales')
            )
            ->groupBy(DB::raw('DATE(transactions.created_at)'))
            ->orderBy('date', 'desc');
    }

    protected function getHeaders(): array
    {
        return [
            'Tanggal',
            'Gross Omset',
            'Diskon',
            'Pajak',
            'Net Omset',
        ];
    }

    protected function mapRow($row): array
    {
        return [
            $row->date,
            (float) $row->gross_sales,
            (float) $row->total_discount,
            (float) $row->total_tax,
            (float) $row->net_sales,
        ];
    }

    protected function getModuleName(): string
    {
        return 'Laporan Penjualan';
    }

    protected function getFileName(): string
    {
        return 'sales_export_'.time().'.csv';
    }
}
