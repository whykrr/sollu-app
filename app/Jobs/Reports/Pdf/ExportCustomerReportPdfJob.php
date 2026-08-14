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

class ExportCustomerReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $outletIds,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        $this->outletIds = array_filter($this->outletIds);
    }

    public function handle(): void
    {
        $data = DB::table('transactions')
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
            ->orderBy('total_spent', 'desc')
            ->limit(1000)
            ->get();

        $fileName = 'customer_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.customer_report', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Pelanggan',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
