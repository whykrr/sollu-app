<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\PromotionReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionReportController extends Controller
{
    public function index(Request $request, PromotionReportService $service)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $outletId = $request->get('outlet', '');

        $now = Carbon::now();
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();

        $data = $service->getReport($outletId, $startDate, $endDate);

        return Inertia::render('Reports/Promotions/Index', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'outlet' => $outletId,
            ],
            'promotions' => $data,
        ]);
    }
}
