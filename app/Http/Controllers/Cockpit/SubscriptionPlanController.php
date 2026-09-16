<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\StoreSubscriptionPlanRequest;
use App\Http\Requests\Cockpit\UpdatePlanFeaturesRequest;
use App\Http\Requests\Cockpit\UpdateSubscriptionPlanRequest;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        $plans = SubscriptionPlan::query()
            ->with(['systemFeatures', 'business:id,name,owner_name,email'])
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', SubscriptionStatus::Active->value)
                    ->where(function ($q) {
                        $q->whereNull('expired_at')
                            ->orWhere('expired_at', '>=', now());
                    })
                    ->whereHas('business', function ($b) {
                        $b->where('status', 'active');
                    })
                    ->select(DB::raw('count(distinct business_id)'));
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

        SubscriptionPlan::clearCache();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function show(string $id): JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->with(['systemFeatures', 'business:id,name,owner_name,email'])
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', SubscriptionStatus::Active->value)
                    ->where(function ($q) {
                        $q->whereNull('expired_at')
                            ->orWhere('expired_at', '>=', now());
                    })
                    ->whereHas('business', function ($b) {
                        $b->where('status', 'active');
                    })
                    ->select(DB::raw('count(distinct business_id)'));
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
        SubscriptionPlan::clearCache();

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

    public function toggleVisibility(Request $request, string $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $plan->update([
            'is_public' => ! $plan->is_public,
        ]);

        $visibilityText = $plan->is_public ? 'ditampilkan di katalog publik' : 'disembunyikan dari katalog publik';

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            "Paket langganan {$plan->name} berhasil {$visibilityText}."
        );
    }

    public function updateFeatures(UpdatePlanFeaturesRequest $request, string $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $validated = $request->validated();
        $featureIds = $validated['feature_ids'] ?? [];

        $plan->systemFeatures()->sync($featureIds);
        $plan->clearFeatureCache();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            "Hak akses fitur sistem untuk paket {$plan->name} berhasil diperbarui!"
        );
    }

    public function destroy(string $id): RedirectResponse
    {
        $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                "Paket langganan {$plan->name} tidak dapat dihapus karena masih memiliki riwayat langganan merchant. Anda dapat menonaktifkannya."
            );
        }

        $plan->systemFeatures()->detach();
        $plan->delete();
        SubscriptionPlan::clearCache();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::PURGE_SUCCESS
        );
    }
}
