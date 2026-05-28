<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $firstDateThisMonth = Carbon::now()->startOfMonth()->toDateString();
        $todayDate          = Carbon::now()->toDateString();

        $getAllDates = function (string $start, string $end, string $format = 'Y-m-d') {
            $dates    = [];
            $current  = Carbon::parse($start);
            $last     = Carbon::parse($end);
            $interval = $current->diffInDays($last);

            for ($i = 0; $i <= $interval; $i++) {
                $dates[] = $current->format($format);
                $current->addDay();
            }

            return $dates;
        };

        $getDummySalesTrend = function (string $start, string $end) {
            $data     = [];
            $current  = Carbon::parse($start);
            $end      = Carbon::parse($end);
            $interval = $current->diffInDays($end);

            for ($i = 0; $i <= $interval; $i++) {
                $data[] = random_int(0, 1000000);
                $current->addDay();
            }

            return $data;
        };

        $allDates = $getAllDates($firstDateThisMonth, $todayDate, 'd');

        return inertia(
            'Overview/Index',
            [
                'filters' => [
                    'type'   => $request->get('type', 'month'),
                    'outlet' => $request->get('outlet'),
                ],
                'totalSales' => [
                    'now'      => 1980000,
                    'previous' => 1730000,
                ],
                'totalTransactions' => [
                    'now'      => 35,
                    'previous' => 46,
                ],
                'averageSales' => [
                    'now'      => 187532,
                    'previous' => 184165,
                ],

                'salesTrend' => [
                    'label' => $getAllDates($firstDateThisMonth, $todayDate, 'd'),
                    'value' => [
                        [
                            'title' => 'Bulan Ini',
                            'data'  => $getDummySalesTrend($firstDateThisMonth, $todayDate),
                        ],
                        [
                            'title' => 'Bulan Lalu',
                            'data'  => $getDummySalesTrend($firstDateThisMonth, $todayDate),
                        ],
                    ],
                ],
                'categorySalesTrend' => [
                    'label' => [
                        'Atasan',
                        'Bawahan',
                        'Outer',
                        'Aksesoris',
                        'Sepatu',
                    ],
                    'value' => [
                        170,
                        380,
                        780,
                        80,
                        90,
                    ],
                ],

                'mostSoldProducts' => [
                    [
                        'name'    => 'Product A',
                        'total'   => 150,
                        'revenue' => 1500000,
                    ],
                    [
                        'name'    => 'Product B',
                        'total'   => 120,
                        'revenue' => 1500000,
                    ],
                    [
                        'name'    => 'Product C',
                        'total'   => 100,
                        'revenue' => 1500000,
                    ],
                    [
                        'name'    => 'Product D',
                        'total'   => 80,
                        'revenue' => 1500000,
                    ],
                    [
                        'name'    => 'Product E',
                        'total'   => 60,
                        'revenue' => 1500000,
                    ],
                ],

                'lowStockProduct' => [
                    [
                        'name'  => 'Product X',
                        'stock' => 2,
                    ],
                    [
                        'name'  => 'Product Y',
                        'stock' => 3,
                    ],
                    [
                        'name'  => 'Product Z',
                        'stock' => 5,
                    ],
                ],
                'productNotSold' => [
                    [
                        'name' => 'Product M',
                    ],
                    [
                        'name' => 'Product N',
                    ],
                    [
                        'name' => 'Product O',
                    ],
                ],
            ]
        );
    }
}
