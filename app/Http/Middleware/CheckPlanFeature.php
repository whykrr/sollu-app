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
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();

        if (! $user || (! $user->business_id && ! $user->relationLoaded('business'))) {
            return $this->rejectAccess($request, $features[0] ?? '');
        }

        if (empty($features)) {
            return $next($request);
        }

        // Fast-path: Check from cached user summary (which already respects personalized business features)
        $summary = SummaryUser::make($user)->cached();
        $userFeatures = (is_array($summary) && array_key_exists('features', $summary))
            ? $summary['features']
            : null;

        $business = null;
        if ($userFeatures === null) {
            $business = $user->relationLoaded('business')
                ? $user->business
                : ($user->business_id ? Business::findCached($user->business_id) : null);
        }

        foreach ($features as $featureName) {
            $feature = FeatureEnum::tryFrom($featureName);
            if (! $feature) {
                continue;
            }

            if ($userFeatures !== null) {
                $hasFeature = in_array($feature, $userFeatures, true)
                    || in_array($feature->value, $userFeatures, true);

                if ($hasFeature) {
                    return $next($request);
                }
            } elseif ($business && $business->hasFeature($feature)) {
                return $next($request);
            }
        }

        return $this->rejectAccess($request, implode(',', $features));
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
