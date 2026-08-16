<?php

namespace App\Jobs\Reports;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportStockReportJob extends AbstractExcelExportJob
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
        $outletIds = $this->outletIds;

        $balanceSub = DB::table('inventory_balances')
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('outlet_id', $outletIds);
            })
            ->select('inventory_item_id', DB::raw('SUM(current_stock) as current_stock'))
            ->groupBy('inventory_item_id');

        return DB::table('inventory_movements')
            ->join('inventory_items', 'inventory_movements.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoinSub($balanceSub, 'balances', function ($join) {
                $join->on('inventory_movements.inventory_item_id', '=', 'balances.inventory_item_id');
            })
            ->when(! empty($outletIds), function ($query) use ($outletIds) {
                $query->whereIn('inventory_movements.outlet_id', $outletIds);
            })
            ->whereBetween('inventory_movements.created_at', [$this->startDate, $this->endDate])
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                DB::raw('COALESCE(balances.current_stock, 0) as closing_stock'),
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change > 0 THEN inventory_movements.qty_change ELSE 0 END) as stock_in'),
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change < 0 THEN ABS(inventory_movements.qty_change) ELSE 0 END) as stock_out')
            )
            ->groupBy('inventory_items.id', 'inventory_items.name', 'balances.current_stock')
            ->orderBy('inventory_items.name', 'asc');
    }

    public function getHeaders(): array
    {
        return [
            'Nama Item',
            'Stok Awal',
            'Masuk',
            'Keluar',
            'Stok Akhir',
        ];
    }

    public function mapRow($row): array
    {
        $stockIn = (float) $row->stock_in;
        $stockOut = (float) $row->stock_out;
        $closingStock = (float) $row->closing_stock;
        $netMovement = $stockIn - $stockOut;
        $startingStock = $closingStock - $netMovement;

        return [
            $row->item_name ?? '-',
            $startingStock,
            $stockIn,
            $stockOut,
            $closingStock,
        ];
    }

    public function getModuleName(): string
    {
        return 'Laporan Stok';
    }

    public function getFileName(): string
    {
        return 'stocks_export_'.time().'.csv';
    }
}
