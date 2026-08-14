<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\Reports\ExportStockReportJob;
use App\Services\Reports\StockAssetReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StockAssetReportController extends Controller
{
    public function index(Request $request, StockAssetReportService $service)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet') ?? '';

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        $data = $service->getReport($outletId, $startDate, $endDate);

        return Inertia::render('Reports/Stocks/Index', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'outlet' => $outletId,
            ],
            'stocks' => $data,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet') ?? '';

        $now = \Carbon\Carbon::now();
        $startDate = $startDateParam ? \Carbon\Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? \Carbon\Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        \App\Jobs\Reports\Pdf\ExportStockReportPdfJob::dispatch(\Illuminate\Support\Facades\Auth::user(), (array) $outletId, $startDate, $endDate);

        return redirect()->back()->with('success', 'Proses ekspor PDF sedang berjalan di latar belakang. Anda akan menerima notifikasi jika sudah selesai.');
    }

    public function exportCsv(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet') ?? '';

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        ExportStockReportJob::dispatch(Auth::user(), (array) $outletId, $startDate, $endDate);

        return redirect()->back()->with('success', 'Proses ekspor CSV sedang berjalan di latar belakang. Anda akan menerima notifikasi jika sudah selesai.');
    }
}
