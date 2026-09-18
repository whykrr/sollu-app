<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\GetCockpitDashboardRequest;
use App\Services\Cockpit\CockpitDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected CockpitDashboardService $dashboardService
    ) {}

    /**
     * Display Cockpit SaaS overview dashboard.
     */
    public function index(GetCockpitDashboardRequest $request): Response
    {
        $period = (string) $request->validated('period', 'this_month');
        $dashboardData = $this->dashboardService->getDashboardData($period);

        return Inertia::render('Cockpit/Dashboard/Index', [
            'metrics' => $dashboardData['metrics'],
            'revenueTrend' => $dashboardData['revenue_trend'],
            'acquisitionTrend' => $dashboardData['acquisition_trend'],
            'planDistribution' => $dashboardData['plan_distribution'],
            'businessTypeDistribution' => $dashboardData['business_type_distribution'],
            'pendingInvoices' => $dashboardData['pending_invoices'],
            'recentMerchants' => $dashboardData['recent_merchants'],
            'filters' => [
                'period' => $period,
                'period_label' => $dashboardData['period_label'],
            ],
        ]);
    }
}
