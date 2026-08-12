<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    /**
     * Handle the incoming request for Dashboard Overview.
     */
    public function __invoke(Request $request)
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

        // Helper for labels
        $getAllDates = function (Carbon $start, Carbon $end, string $format = 'd M') {
            $dates = [];
            $current = $start->copy();
            $interval = $current->diffInDays($end);

            for ($i = 0; $i <= $interval; $i++) {
                $dates[] = $current->format($format);
                $current->addDay();
            }

            return $dates;
        };

        // Dummy trend generator
        $getDummySalesTrend = function (Carbon $start, Carbon $end) {
            $data = [];
            $current = $start->copy();
            $interval = $current->diffInDays($end);

            for ($i = 0; $i <= $interval; $i++) {
                $data[] = random_int(100000, 1500000);
                $current->addDay();
            }

            return $data;
        };

        $chartLabels = $getAllDates($startDate, $endDate, $period === 'today' ? 'H:00' : 'd M');
        if ($period === 'today') {
            $chartLabels = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
            $trendNowData = [150000, 320000, 540000, 280000, 420000, 680000, 890000, 310000];
            $trendPrevData = [120000, 290000, 480000, 230000, 390000, 590000, 750000, 280000];
        } else {
            $trendNowData = $getDummySalesTrend($startDate, $endDate);
            $trendPrevData = $getDummySalesTrend($prevStartDate, $prevEndDate);
        }

        return inertia('Overview/Index', [
            'filters' => [
                'period' => $period,
                'outlet' => $outletId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'period_label' => $periodLabel,
            ],
            'totalSales' => [
                'now' => 3590000,
                'previous' => 3130000,
            ],
            'grossProfit' => [
                'now' => 1436000,
                'previous' => 1125000,
            ],
            'totalTransactions' => [
                'now' => 48,
                'previous' => 41,
            ],
            'averageSales' => [
                'now' => 74791,
                'previous' => 76341,
            ],
            'salesTrend' => [
                'label' => $chartLabels,
                'value' => [
                    [
                        'title' => 'Periode Ini',
                        'data' => $trendNowData,
                    ],
                    [
                        'title' => 'Periode Lalu',
                        'data' => $trendPrevData,
                    ],
                ],
            ],
            'categorySalesTrend' => [
                'label' => ['Makanan & Minuman', 'Pakaian & Aksesoris', 'Bahan Baku', 'Jasa & Layanan', 'Elektronik'],
                'value' => [1450000, 980000, 620000, 340000, 200000],
            ],
            'paymentMethodSummary' => [
                'label' => ['Tunai', 'QRIS', 'Transfer Bank', 'Kartu EDC'],
                'value' => [45, 35, 12, 8],
                'revenue' => [1615500, 1256500, 430800, 287200],
            ],
            'mostSoldProducts' => [
                [
                    'name' => 'Kopi Susu Gula Aren',
                    'total' => 124,
                    'revenue' => 2480000,
                ],
                [
                    'name' => 'Croissant Almond',
                    'total' => 88,
                    'revenue' => 1760000,
                ],
                [
                    'name' => 'Matcha Latte Ice',
                    'total' => 65,
                    'revenue' => 1430000,
                ],
                [
                    'name' => 'Nasi Goreng Special',
                    'total' => 52,
                    'revenue' => 1300000,
                ],
                [
                    'name' => 'Beef Burger Combo',
                    'total' => 41,
                    'revenue' => 1230000,
                ],
            ],
            'lowStockProduct' => [
                [
                    'name' => 'Susu UHT Full Cream 1L',
                    'stock' => 2,
                    'min_stock' => 10,
                ],
                [
                    'name' => 'Bijikopi Espresso Blend 1kg',
                    'stock' => 3,
                    'min_stock' => 8,
                ],
                [
                    'name' => 'Syrup Vanilla 700ml',
                    'stock' => 4,
                    'min_stock' => 5,
                ],
            ],
            'productNotSold' => [
                [
                    'name' => 'Teh Chamomile Loose Leaf',
                ],
                [
                    'name' => 'Red Velvet Muffin',
                ],
                [
                    'name' => 'Sparkling Water 330ml',
                ],
            ],
        ]);
    }
}
