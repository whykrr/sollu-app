<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessInfoController extends Controller
{
    /**
     * Retrieve optimized business summary info for internal UI components.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $user->business_id;

        /** @var Business|null $business */
        $business = Business::query()
            ->where('id', $businessId)
            ->select(['id', 'business_type_id'])
            ->with(['type:id,name'])
            ->first();

        if (! $business) {
            return response()->json(['message' => 'Business not found'], 404);
        }

        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->select(['id', 'business_id', 'plan_id', 'expired_at', 'is_trial'])
            ->with(['plan:id,name'])
            ->latest('started_at')
            ->first();

        if ($subscription) {
            $baseName = $subscription->plan?->name ?? 'Default Plan';
            $planName = $subscription->is_trial ? "{$baseName} (Trial)" : $baseName;
            $expiredAt = $subscription->expired_at?->toISOString() ?? (string) $subscription->expired_at;
        } else {
            $planName = 'Free Plan';
            $expiredAt = null;
        }

        $outletCount = Outlet::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->count();

        return response()->json([
            'business_type' => $business->type?->name ?? 'Unknown',
            'plan_name' => $planName,
            'expired_at' => $expiredAt,
            'outlet_count' => (int) $outletCount,
        ]);
    }
}
