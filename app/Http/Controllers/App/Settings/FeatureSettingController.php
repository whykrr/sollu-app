<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FeatureSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_VIEW->value);

        /** @var Business */
        $business = Auth::user()->business;
        $business->loadMissing('type');

        // Level 1: BusinessType supported features
        $type = $business->type;
        $businessTypeFeatureCodes = is_array($type?->features) ? $type->features : null;

        // Level 2: Base Plan Features (used to determine what's locked vs available to toggle)
        $availableFeatures = array_map(fn ($f) => $f->value, $business->getAvailablePlanFeatures());

        // Level 3: Actual Toggled ON features (taking into account user settings & defaults & plan intersection)
        $activeFeatures = array_map(fn ($f) => $f->value, $business->activePlanFeatures());

        // Feature groups filtered by BusinessType if configured
        $allGroups = Feature::grouped();
        if ($businessTypeFeatureCodes !== null) {
            $filteredGroups = [];
            foreach ($allGroups as $groupName => $featuresList) {
                $filteredItems = array_values(array_filter($featuresList, function ($feat) use ($businessTypeFeatureCodes) {
                    return in_array($feat['value'], $businessTypeFeatureCodes, true);
                }));
                if (! empty($filteredItems)) {
                    $filteredGroups[$groupName] = $filteredItems;
                }
            }
            $featureGroups = $filteredGroups;
        } else {
            $featureGroups = $allGroups;
        }

        return Inertia::render('Settings/Business/Features', [
            'availableFeatures' => $availableFeatures,
            'activeFeatures' => $activeFeatures,
            'featureGroups' => $featureGroups,
        ]);
    }

    public function save(Request $request)
    {
        $this->authorize(PermissionEnum::BUSINESS_UPDATE->value);

        $request->validate([
            'features' => ['required', 'array'],
            'features.*' => ['string'],
        ]);

        $business_id = Auth::user()->business_id;

        /** @var Business */
        $business = Business::findOrFail($business_id);
        $business->loadMissing('type');

        $settings = $business->settings ?? [];

        // We only allow enabling features that are allowed by both BusinessType (Level 1) and Plan (Level 2)
        $availablePlanFeatures = array_map(fn ($f) => $f->value, $business->getAvailablePlanFeatures());
        $type = $business->type;
        $typeFeatures = is_array($type?->features) ? $type->features : null;

        $allowedPool = $typeFeatures !== null
            ? array_values(array_intersect($typeFeatures, $availablePlanFeatures))
            : $availablePlanFeatures;

        $requestedFeatures = $request->input('features');
        $validFeaturesToSave = array_values(array_intersect($requestedFeatures, $allowedPool));

        $settings['active_features'] = $validFeaturesToSave;

        $business->settings = $settings;
        $business->save();

        Business::clearCache($business->id);
        $rootUser = $business->users()->where('is_root_user', true)->first();
        if ($rootUser) {
            SummaryUser::cacheDelete($rootUser->id);
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
