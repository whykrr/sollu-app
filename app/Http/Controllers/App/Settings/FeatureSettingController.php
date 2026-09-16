<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
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

        // Base Plan Features (used to determine what's locked vs available to toggle)
        $availableFeatures = $business->getAvailablePlanFeatures();

        // Actual Toggled ON features (taking into account user settings & defaults & plan intersection)
        $activeFeatures = $business->activePlanFeatures($availableFeatures);

        return Inertia::render('Settings/Business/Features', [
            'availableFeatures' => array_map(fn ($f) => $f->value, $availableFeatures),
            'activeFeatures' => array_map(fn ($f) => $f->value, $activeFeatures),
            'featureGroups' => Feature::grouped(),
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

        $settings = $business->settings ?? [];

        // We only allow enabling features that are actually in their plan
        $availablePlanFeatures = array_map(fn ($f) => $f->value, $business->getAvailablePlanFeatures());
        $requestedFeatures = $request->input('features');

        $validFeaturesToSave = array_values(array_intersect($requestedFeatures, $availablePlanFeatures));

        $settings['active_features'] = $validFeaturesToSave;

        $business->settings = $settings;
        $business->save();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
