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

class ExportCashierReportPdfJob implements ShouldQueue
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

        $data = DB::table('shifts')
            ->join('users', 'shifts.user_id', '=', 'users.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('shifts.outlet_id', $this->outletIds);
            })
            ->whereBetween('shifts.created_at', [$this->startDate, $this->endDate])
            ->select(
                'shifts.id',
                'shifts.created_at as opened_at',
                'shifts.closed_at',
                'users.name as cashier_name',
                'shifts.opening_cash as starting_cash',
                'shifts.expected_cash as expected_ending_cash',
                'shifts.closing_cash as actual_ending_cash',
                'shifts.status',
                DB::raw('(COALESCE(shifts.closing_cash, 0) - COALESCE(shifts.expected_cash, 0)) as difference')
            )
            ->orderBy('shifts.created_at', 'desc')
            ->limit(1000)
            ->get();

        $fileName = 'cashier_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.cashier_report', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Kasir',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
