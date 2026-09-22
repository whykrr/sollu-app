<?php

namespace App\Http\Controllers\App\Reports;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\DatePresetEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Reports\ExportReportRequest;
use App\Http\Requests\App\Reports\GetCashierReportRequest;
use App\Jobs\Reports\ExportCashierReportJob;
use App\Jobs\Reports\Pdf\ExportCashierReportPdfJob;
use App\Services\App\Reports\CashierShiftReportService;
use App\Services\App\Reports\ReportOutletResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CashierShiftReportController extends Controller
{
    public function index(GetCashierReportRequest $request, CashierShiftReportService $service): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $businessId = (string) $user->business_id;

        $range = DatePresetEnum::resolveRange(
            $request->input('period'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $outletIds = ReportOutletResolver::resolve($user, $request->input('outlet'));
        $startDate = Carbon::parse($range['start_date'])->startOfDay();
        $endDate = Carbon::parse($range['end_date'])->endOfDay();

        $data = $service->getReport(
            $businessId,
            $outletIds,
            $startDate,
            $endDate,
            $request->validated()
        );

        return Inertia::render('Reports/Cashiers/Index', [
            'filters' => [
                'period' => $range['preset'],
                'start_date' => $range['start_date'],
                'end_date' => $range['end_date'],
                'outlet' => $request->input('outlet', ''),
                'search' => $request->input('search', ''),
            ],
            'summary' => $data['summary'],
            'shifts' => $data['shifts'],
        ]);
    }

    public function exportPdf(ExportReportRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $range = DatePresetEnum::resolveRange(
            $request->input('period'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $outletIds = ReportOutletResolver::resolve($user, $request->input('outlet'));
        $startDate = Carbon::parse($range['start_date'])->startOfDay();
        $endDate = Carbon::parse($range['end_date'])->endOfDay();

        ExportCashierReportPdfJob::dispatch($user, $outletIds, $startDate, $endDate);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }

    public function exportCsv(ExportReportRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $range = DatePresetEnum::resolveRange(
            $request->input('period'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $outletIds = ReportOutletResolver::resolve($user, $request->input('outlet'));
        $startDate = Carbon::parse($range['start_date'])->startOfDay();
        $endDate = Carbon::parse($range['end_date'])->endOfDay();

        ExportCashierReportJob::dispatch($user, $outletIds, $startDate, $endDate);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }
}
