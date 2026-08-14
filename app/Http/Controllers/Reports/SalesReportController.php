<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\Reports\ExportSalesReportJob;
use App\Services\Reports\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesReportService $service)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet', '');

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        $data = $service->getReport($outletId, $startDate, $endDate);

        return Inertia::render('Reports/Sales/Index', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'outlet' => $outletId,
            ],
            'dailySales' => $data['daily_sales'],
            'paymentMethods' => $data['payment_methods'],
        ]);
    }

    public function exportPdf(Request $request, \App\Services\Reports\SalesReportService $service)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet', '');

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        $data = $service->getReport($outletId, $startDate, $endDate);

        $pdf = Pdf::loadView('pdf.reports.sales', [
            'data' => $data,
            'business' => Auth::user()->business,
            'outlet' => Auth::user()->activeOutlet,
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales_report_'.now()->format('YmdHis').'.pdf');
    }

    public function exportCsv(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet', '');

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        ExportSalesReportJob::dispatch(Auth::user(), (array) $outletId, $startDate, $endDate);

        return redirect()->back()->with('success', 'Proses ekspor CSV sedang berjalan di latar belakang. Anda akan menerima notifikasi jika sudah selesai.');
    }
}
