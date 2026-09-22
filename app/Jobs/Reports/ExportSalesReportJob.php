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
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'pgsql' => "to_char(transactions.created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', transactions.created_at)",
            default => 'DATE(transactions.created_at)',
        };

        return DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->where('outlets.business_id', $this->user->business_id)
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
            ->select(
                DB::raw("$dateExpr as date"),
                DB::raw('COALESCE(SUM(transactions.subtotal), 0) as gross_sales'),
                DB::raw('COALESCE(SUM(transactions.discount_amount), 0) as total_discount'),
                DB::raw('COALESCE(SUM(transactions.tax_amount), 0) as total_tax'),
                DB::raw('COALESCE(SUM(transactions.total), 0) as net_sales')
            )
            ->groupByRaw($dateExpr)
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
