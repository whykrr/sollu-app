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
        $firstDateSixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth()->toDateString();
        $firstDateThisMonth    = Carbon::now()->startOfMonth()->toDateString();
        $today                 = Carbon::now()->toDateString();

        return inertia(
            'Dashboard/Index',
            [
                'visits'           => 0,
                'visitorThisMonth' => [
                    'date'     => '2025-05',
                    'visitors' => 0,
                ],
                'messageUnread'          => 0,
                'visitorPerMonthPerPage' => [
                    'label' => [
                        '2024-11',
                        '2024-12',
                        '2025-01',
                        '2025-02',
                        '2025-03',
                        '2025-04',
                    ],
                    'value' => [
                        [
                            'url'   => 'http://sollu.id',
                            'value' => [
                                0,
                                0,
                                7,
                                2,
                                0,
                                0,
                            ],
                        ],
                    ],
                ],
                'visitorPerMonth' => [
                    'label' => [
                        '2024-11',
                        '2024-12',
                        '2025-01',
                        '2025-02',
                        '2025-03',
                        '2025-04',
                    ],
                    'value' => [
                        0,
                        0,
                        7,
                        2,
                        0,
                        0,
                    ],
                ],
                'pageMostVisits' => [
                    [
                        'url'    => 'http://sollu.test',
                        'visits' => 9,
                    ],
                ],
            ]
        );
    }
}
