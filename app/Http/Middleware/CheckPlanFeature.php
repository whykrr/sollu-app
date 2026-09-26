<?php

namespace App\Http\Middleware;

use App\Enums\FeatureEnum;
use App\Helpers\SummaryUser;
use App\Models\Business;
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

        if (! $user || (! $user->business_id && ! $user->relationLoaded('business'))) {
            return $this->rejectAccess($request, $featureName);
        }

        $feature = FeatureEnum::tryFrom($featureName);

        if (! $feature) {
            return $this->rejectAccess($request, $featureName);
        }

        // Fast-path: Check from cached user summary (which already respects personalized business features)
        $summary = SummaryUser::make($user)->cached();
        if (is_array($summary) && array_key_exists('features', $summary)) {
            $hasFeature = in_array($feature, $summary['features'], true)
                || in_array($feature->value, $summary['features'], true);

            if (! $hasFeature) {
                return $this->rejectAccess($request, $featureName);
            }

            return $next($request);
        }

        // Fallback: Resolve via cached Business model without triggering un-cached database queries
        $business = $user->relationLoaded('business')
            ? $user->business
            : ($user->business_id ? Business::findCached($user->business_id) : null);

        if (! $business || ! $business->hasFeature($feature)) {
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
