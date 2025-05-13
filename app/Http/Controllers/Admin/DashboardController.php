<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Visitor;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $firstDateSixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth()->toDateString();
        $firstDateThisMonth = Carbon::now()->startOfMonth()->toDateString();
        $today = Carbon::now()->toDateString();

        $period = CarbonPeriod::create($firstDateSixMonthsAgo, '1 month', $today);

        $months = collect($period)->map(function ($date) {
            return $date->format('Y-m');
        });

        $totalVisits = Visitor::filters([
            'from' => $firstDateThisMonth,
            'to' => $today
        ])->count();

        $getPageMostVisits = Visitor::selectRaw('url, count(url) as visits')
            ->filters([
                'from' => $firstDateSixMonthsAgo,
                'to' => $today
            ])
            ->groupBy('url')
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get();

        $visitorPerMonthPerPageModel = Visitor::selectRaw('created_month as date, url, COUNT(DISTINCT ip_address, session_id) as visitors')
            ->filters([
                'month_from' => $months[0],
                'month_to' => $months[6]
            ])
            ->whereIn('url', $getPageMostVisits->take(5)->pluck('url')->toArray())
            ->groupBy(['date', 'url'])
            ->orderBy('date', 'asc')
            ->get();

        $visitorPerMonthColl = $visitorPerMonthPerPageModel->groupBy('date')
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'visitors' => $group->sum('visitors')
                ];
            });

        $visitorPerMonth = $months->map(function ($month) use ($visitorPerMonthColl) {
            return $visitorPerMonthColl->firstWhere('date', $month) ?? ['date' => $month, 'visitors' => 0];
        });


        $visitorPerMonthPerPage = [];
        foreach ($getPageMostVisits->take(5) as $page) {
            $visitorPerMonthPerPage[] = [
                'url' => $page->url,
                'value' => $months->map(function ($month) use ($visitorPerMonthPerPageModel, $page) {
                    return $visitorPerMonthPerPageModel->where('date', $month)->where('url', $page->url)->first()->visitors ?? 0;
                })->take(6)
            ];
        }

        $messageUnread = Message::filters(['status' => 'unread'])->count();

        return inertia(
            'Dashboard/Index',
            [
                'visits' => $totalVisits,
                'visitorThisMonth' => $visitorPerMonth->pop(),
                'messageUnread' => $messageUnread,
                'visitorPerMonthPerPage' => [
                    'label' => $months->take(6),
                    'value' => $visitorPerMonthPerPage
                ],
                'visitorPerMonth' => [
                    'label' => $months->take(6),
                    'value' => $visitorPerMonth->pluck('visitors')->take(6)
                ],
                'pageMostVisits' => $getPageMostVisits
            ]
        );
    }
}
