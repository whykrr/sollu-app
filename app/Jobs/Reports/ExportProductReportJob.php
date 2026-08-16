<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportProductReportJob extends AbstractExcelExportJob
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
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
            ->select(
                'products.name as product_name',
                'product_categories.name as category_name',
                DB::raw('SUM(transaction_items.qty) as total_qty'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_qty');
    }

    public function getHeaders(): array
    {
        return [
            'Nama Produk',
            'Kategori',
            'Qty Terjual',
            'Total Penjualan',
        ];
    }

    public function mapRow($row): array
    {
        return [
            $row->product_name ?? '-',
            $row->category_name ?? '-',
            (float) $row->total_qty,
            (float) $row->total_sales,
        ];
    }

    public function getModuleName(): string
    {
        return 'Laporan Produk';
    }

    public function getFileName(): string
    {
        return 'products_export_'.time().'.csv';
    }
}
