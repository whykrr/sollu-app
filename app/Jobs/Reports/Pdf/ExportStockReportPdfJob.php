<?php

namespace App\Jobs\Reports\Pdf;

use App\Models\User;
use App\Notifications\DocumentExportCompleted;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportStockReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $userId;

    protected ?User $user = null;

    public function __construct(
        User $user,
        public array $outletIds,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        $this->userId = $user->id;
        $this->outletIds = array_filter($this->outletIds);
    }

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        Storage::disk('local')->makeDirectory('exports');

        $movements = DB::table('inventory_movements')
            ->join('inventory_items', 'inventory_movements.inventory_item_id', '=', 'inventory_items.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('inventory_movements.outlet_id', $this->outletIds);
            })
            ->whereBetween('inventory_movements.created_at', [$this->startDate, $this->endDate])
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change > 0 THEN inventory_movements.qty_change ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN inventory_movements.qty_change < 0 THEN ABS(inventory_movements.qty_change) ELSE 0 END) as total_out')
            )
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->limit(1000)
            ->get();

        $balances = DB::table('inventory_balances')
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('inventory_balances.outlet_id', $this->outletIds);
            })
            ->select(
                'inventory_items.id as item_id',
                'inventory_items.name as item_name',
                DB::raw('SUM(inventory_balances.current_stock) as current_stock')
            )
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->get()
            ->keyBy('item_id');

        $data = [];
        foreach ($movements as $m) {
            $currentStock = isset($balances[$m->item_id]) ? $balances[$m->item_id]->current_stock : 0;
            $netMovement = $m->total_in - $m->total_out;
            $startingStock = $currentStock - $netMovement;

            $data[] = [
                'item_name' => $m->item_name,
                'starting_stock' => $startingStock,
                'stock_in' => $m->total_in,
                'stock_out' => $m->total_out,
                'closing_stock' => $currentStock,
                'asset_value' => 0,
            ];
        }

        $fileName = 'stock_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.stock_report', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Stok',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
