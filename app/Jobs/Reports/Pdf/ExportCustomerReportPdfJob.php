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

        Storage::disk('public')->makeDirectory('exports');

        $data = DB::table('transactions')
            ->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('outlets.business_id', $this->user->business_id)
            ->where('customers.business_id', $this->user->business_id)
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
                DB::raw('COALESCE(SUM(transactions.total), 0) as total_spent'),
                DB::raw('MAX(transactions.created_at) as last_visit')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderBy('total_spent', 'desc')
            ->limit(1000)
            ->get();

        $fileName = 'customer_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.customers', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('public')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Pelanggan',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
