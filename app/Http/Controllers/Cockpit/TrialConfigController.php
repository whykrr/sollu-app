<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Enums\PlanEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\UpdateTrialConfigRequest;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TrialConfigController extends Controller
{
    /**
     * Get trial configuration data including selected features and all system features.
     */
    public function show(): JsonResponse
    {
        $durationDays = SystemSetting::getTrialDurationDays();
        $rawFeatures = SystemSetting::get('trial_features', null);

        if ($rawFeatures === null) {
            $basicPlan = SubscriptionPlan::findByCodeCached(PlanEnum::BASIC->value);
            $features = $basicPlan ? $basicPlan->activeFeatureCodes() : [];
        } else {
            $features = is_array($rawFeatures) ? $rawFeatures : (json_decode((string) $rawFeatures, true) ?: []);
        }

        return response()->json([
            'duration_days' => $durationDays,
            'features' => $features,
            'all_features' => Feature::getAllCached(),
        ]);
    }

    /**
     * Update trial configuration and refresh cached trial enums.
     */
    public function update(UpdateTrialConfigRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        SystemSetting::set('trial_default_duration_days', (int) $validated['duration_days'], 'subscription');
        SystemSetting::set('trial_features', $validated['features'], 'subscription');
        SystemSetting::clearTrialCache();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pengaturan masa uji coba (trial) berhasil diperbarui!'
        );
    }
}
