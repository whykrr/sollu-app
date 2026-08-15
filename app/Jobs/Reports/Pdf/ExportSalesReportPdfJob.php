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

class ExportSalesReportPdfJob implements ShouldQueue
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

        $dailySales = DB::table('transactions')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('outlet_id', $this->outletIds);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(subtotal) as gross_sales'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(total) as net_sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->limit(1000)
            ->get();

        $paymentMethods = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->join('payment_methods', 'transaction_payments.payment_method_id', '=', 'payment_methods.id')
            ->when(! empty($this->outletIds), function ($query) {
                $query->whereIn('transactions.outlet_id', $this->outletIds);
            })
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$this->startDate, $this->endDate])
            ->select(
                'payment_methods.name as payment_name',
                DB::raw('COUNT(transaction_payments.id) as total_transactions'),
                DB::raw('SUM(transaction_payments.amount) as total_revenue')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->limit(1000)
            ->get();

        $data = [
            'daily_sales' => $dailySales,
            'payment_methods' => $paymentMethods,
        ];

        $fileName = 'sales_report_'.time().'.pdf';

        $pdf = Pdf::loadView('pdf.reports.sales_report', [
            'data' => $data,
            'business' => $this->user->business,
            'outlet' => $this->user->activeOutlet,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('local')->put('exports/'.$fileName, $pdf->output());

        $this->user->notify(new DocumentExportCompleted(
            'Laporan Penjualan',
            $fileName,
            route('exports.download', ['file' => $fileName]),
            now()->addDays(1)
        ));
    }
}
