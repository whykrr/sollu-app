<?php

namespace App\Http\Controllers\App\Overview;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Overview\GetOverviewRequest;
use App\Services\App\Reports\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Handle the incoming request for Dashboard Overview.
     */
    public function __invoke(GetOverviewRequest $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $businessId = (string) $user->business_id;

        $filters = $request->validated();
        $dashboardData = $this->dashboardService->getDashboardData($businessId, $filters);

        return Inertia::render('Overview/Index', $dashboardData);
    }
}
