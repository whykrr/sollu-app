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

class ExportPromotionReportPdfJob implements ShouldQueue
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

        $data = DB::table('transaction_promos')
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
            ->orderBy('total_usage', 'desc')
            ->limit(1000)
            ->get();

        $fileName = 'promotion_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.promotion_report', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Promosi',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
