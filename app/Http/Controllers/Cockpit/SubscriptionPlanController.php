<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\StoreSubscriptionPlanRequest;
use App\Http\Requests\Cockpit\UpdateSubscriptionPlanRequest;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        $plans = SubscriptionPlan::query()
            ->with(['systemFeatures'])
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('price_per_outlet', 'asc')
            ->get();

        $allFeatures = Feature::getAllCached();

        return Inertia::render('Cockpit/SubscriptionPlan/Index', [
            'plans' => $plans,
            'allFeatures' => $allFeatures,
        ]);
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $systemFeatureIds = $validated['system_feature_ids'] ?? [];
        unset($validated['system_feature_ids']);

        $plan = SubscriptionPlan::create($validated);

        if (! empty($systemFeatureIds)) {
            $plan->systemFeatures()->sync($systemFeatureIds);
            $plan->clearFeatureCache();
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function show(string $id): JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->with(['systemFeatures'])
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', 'active');
            }])
            ->findOrFail($id);

        return response()->json([
            ...$plan->toArray(),
            'system_feature_ids' => $plan->systemFeatures->pluck('id')->all(),
            'all_features' => Feature::getAllCached(),
        ]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, string $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $validated = $request->validated();

        if (array_key_exists('system_feature_ids', $validated)) {
            $plan->systemFeatures()->sync($validated['system_feature_ids'] ?? []);
            $plan->clearFeatureCache();
            unset($validated['system_feature_ids']);
        }

        $plan->update($validated);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function toggleStatus(Request $request, string $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $plan->update([
            'is_active' => ! $plan->is_active,
        ]);

        $statusText = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            "Paket langganan {$plan->name} berhasil {$statusText}."
        );
    }
}
