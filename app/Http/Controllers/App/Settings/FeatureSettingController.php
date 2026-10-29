<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FeatureSettingController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

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
            'isPersonalized' => $business->isFeaturePersonalized(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! Auth::user()?->is_root_user, 403);

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
        foreach ($business->users()->pluck('id') as $userId) {
            SummaryUser::cacheDelete($userId);
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'feature_settings.saved',
            description: 'Memperbarui personalisasi fitur usaha',
            subject: $business,
            causer: $request->user(),
            businessId: $business->id,
            properties: [
                'active_features' => $validFeaturesToSave,
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_if(! Auth::user()?->is_root_user, 403);

        $business_id = Auth::user()->business_id;

        /** @var Business */
        $business = Business::findOrFail($business_id);
        $oldFeatures = $business->settings['active_features'] ?? [];

        $business->resetFeaturePersonalization();

        foreach ($business->users()->pluck('id') as $userId) {
            SummaryUser::cacheDelete($userId);
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'feature_settings.reset',
            description: 'Mereset personalisasi fitur usaha ke pengaturan standar paket',
            subject: $business,
            causer: $request->user(),
            businessId: $business->id,
            properties: [
                'old' => ['active_features' => $oldFeatures],
                'new' => ['active_features' => null],
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pengaturan personalisasi fitur berhasil direset ke standar!'
        );
    }
}
