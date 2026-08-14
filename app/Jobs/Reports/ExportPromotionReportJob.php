<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportPromotionReportJob extends AbstractCsvExportJob
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
        return DB::table('transaction_promos')
            ->join('transactions', 'transaction_promos.transaction_id', '=', 'transactions.id')
            ->join('promos', 'transaction_promos.promo_id', '=', 'promos.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
            ->select(
                'promos.name as promo_name',
                'promos.promo_type',
                DB::raw('COUNT(transaction_promos.id) as total_usage'),
                DB::raw('SUM(transaction_promos.discount_amount) as total_discount_given')
            )
            ->groupBy('promos.id', 'promos.name', 'promos.promo_type')
            ->orderBy('total_usage', 'desc');
    }

    protected function getHeaders(): array
    {
        return [
            'Nama Promo',
            'Tipe Promo',
            'Total Pemakaian',
            'Total Diskon Diberikan',
        ];
    }

    protected function mapRow($row): array
    {
        return [
            $row->promo_name ?? '-',
            $row->promo_type ?? '-',
            (int) $row->total_usage,
            (float) $row->total_discount_given,
        ];
    }

    protected function getModuleName(): string
    {
        return 'Laporan Promosi';
    }

    protected function getFileName(): string
    {
        return 'promotions_export_'.time().'.csv';
    }
}
