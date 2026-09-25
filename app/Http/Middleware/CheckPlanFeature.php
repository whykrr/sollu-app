<?php

namespace App\Http\Middleware;

use App\Enums\FeatureEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        $user = $request->user();

        if (! $user || ! $user->business) {
            return $this->rejectAccess($request, $featureName);
        }

        $feature = FeatureEnum::tryFrom($featureName);

        if (! $feature) {
            return $this->rejectAccess($request, $featureName);
        }

        if (! $user->business->hasFeature($feature)) {
            return $this->rejectAccess($request, $featureName);
        }

        return $next($request);
    }

    protected function rejectAccess(Request $request, string $featureName): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'paket langganan Anda tidak mendukung fitur ini. Tingkatkan paket langganan Anda untuk mengakses fitur ini.',
                'is_feature_locked' => true,
                'feature' => $featureName,
            ], 403);
        }

        // For Inertia / regular web requests
        return redirect()->back()->with('feature_locked', [
            'feature' => $featureName,
            'timestamp' => microtime(true),
        ]);
    }
}
