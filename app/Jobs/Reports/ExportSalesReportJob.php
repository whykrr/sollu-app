<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportSalesReportJob extends AbstractExcelExportJob
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

    public function getQuery()
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

    public function getHeaders(): array
    {
        return [
            'Tanggal',
            'Gross Omset',
            'Diskon',
            'Pajak',
            'Net Omset',
        ];
    }

    public function mapRow($row): array
    {
        return [
            $row->date,
            (float) $row->gross_sales,
            (float) $row->total_discount,
            (float) $row->total_tax,
            (float) $row->net_sales,
        ];
    }

    public function getModuleName(): string
    {
        return 'Laporan Penjualan';
    }

    public function getFileName(): string
    {
        return 'sales_export_'.time().'.csv';
    }
}
