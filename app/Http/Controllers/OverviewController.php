<?php

namespace App\Http\Controllers;

use App\Services\Reports\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    /**
     * Handle the incoming request for Dashboard Overview.
     */
    public function __invoke(Request $request, DashboardService $dashboardService)
    {
        $period = $request->get('period', 'today');
        $outletId = $request->get('outlet', '');
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');

        $now = Carbon::now();

        // Calculate Date Range based on Preset
        switch ($period) {
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $prevStartDate = $now->copy()->subDays(2)->startOfDay();
                $prevEndDate = $now->copy()->subDays(2)->endOfDay();
                $periodLabel = 'dari kemarin';
                break;
            case '7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStartDate = $now->copy()->subDays(13)->startOfDay();
                $prevEndDate = $now->copy()->subDays(7)->endOfDay();
                $periodLabel = '7 hari terakhir';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfDay();
                $prevStartDate = $now->copy()->subMonth()->startOfMonth();
                $prevEndDate = $now->copy()->subMonth()->endOfMonth();
                $periodLabel = 'bulan ini';
                break;
            case 'custom':
                $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfDay() : $now->copy()->startOfDay();
                $endDate = $endDateParam ? Carbon::parse($endDateParam)->endOfDay() : $now->copy()->endOfDay();
                $diffDays = max(1, $startDate->diffInDays($endDate));
                $prevStartDate = $startDate->copy()->subDays($diffDays);
                $prevEndDate = $startDate->copy()->subDay();
                $periodLabel = 'periode sebelumnya';
                break;
            case 'today':
            default:
                $period = 'today';
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStartDate = $now->copy()->subDay()->startOfDay();
                $prevEndDate = $now->copy()->subDay()->endOfDay();
                $periodLabel = 'hari ini';
                break;
        }

        $outletIds = array_filter((array) $outletId);
        $metrics = $dashboardService->getMetrics($outletIds, $startDate, $endDate, $prevStartDate, $prevEndDate);
        $salesTrend = $dashboardService->getSalesTrend($outletIds, $startDate, $endDate, $prevStartDate, $prevEndDate, $period === 'today');
        $categorySalesTrend = $dashboardService->getCategorySalesTrend($outletIds, $startDate, $endDate);
        $paymentMethodSummary = $dashboardService->getPaymentMethodSummary($outletIds, $startDate, $endDate);
        $mostSoldProducts = $dashboardService->getMostSoldProducts($outletIds, $startDate, $endDate);
        $lowStockProduct = $dashboardService->getLowStockProducts($outletIds);
        $productNotSold = $dashboardService->getProductNotSold($outletIds, $startDate, $endDate);

        return inertia('Overview/Index', [
            'filters' => [
                'period' => $period,
                'outlet' => $outletId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'period_label' => $periodLabel,
            ],
            'totalSales' => $metrics['totalSales'],
            'totalTransactions' => $metrics['totalTransactions'],
            'averageSales' => $metrics['averageSales'],
            'salesTrend' => $salesTrend,
            'categorySalesTrend' => $categorySalesTrend,
            'paymentMethodSummary' => $paymentMethodSummary,
            'mostSoldProducts' => $mostSoldProducts,
            'lowStockProduct' => $lowStockProduct,
            'productNotSold' => $productNotSold,
        ]);
    }
}
