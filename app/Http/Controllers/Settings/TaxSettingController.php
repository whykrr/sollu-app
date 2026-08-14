<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateTaxSettingRequest;
use App\Models\Outlet;
use App\Services\Outlet\ManageOutletSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxSettingController extends Controller
{
    public function __construct(
        protected ManageOutletSettingService $settingService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_TAX->value);

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        $selectedOutletId = $request->get('outlet_id')
            ?? SelectedOutlet::make()->get()?->id
            ?? $outlets->first()?->id;

        $targetOutlet = $outlets->firstWhere('id', $selectedOutletId) ?? $outlets->first();

        $taxSettings = [
            'financial_tax' => 0,
            'financial_service_fee' => 0,
            'tax_included_in_price' => false,
            'rounding_enabled' => false,
            'rounding_mode' => 'nearest',
        ];

        if ($targetOutlet) {
            $settings = $targetOutlet->settings()
                ->whereIn('key', ['tax', 'service_fee', 'tax_included_in_price', 'rounding_enabled', 'rounding_mode'])
                ->get();

            foreach ($settings as $setting) {
                if ($setting->key === 'tax') {
                    $taxSettings['financial_tax'] = (float) $setting->value;
                } elseif ($setting->key === 'service_fee') {
                    $taxSettings['financial_service_fee'] = (float) $setting->value;
                } elseif (isset($taxSettings[$setting->key])) {
                    $taxSettings[$setting->key] = $setting->value;
                }
            }
        }

        return Inertia::render('Settings/Tax/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'taxSettings' => $taxSettings,
        ]);
    }

    public function update(UpdateTaxSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $outletId = $validated['outlet_id'];

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $settingsArray = [
            [
                'category' => 'financial',
                'key' => 'tax',
                'value' => (float) ($validated['financial_tax'] ?? 0),
            ],
            [
                'category' => 'financial',
                'key' => 'service_fee',
                'value' => (float) ($validated['financial_service_fee'] ?? 0),
            ],
            [
                'category' => 'financial',
                'key' => 'tax_included_in_price',
                'value' => ! empty($validated['tax_included_in_price']),
            ],
            [
                'category' => 'financial',
                'key' => 'rounding_enabled',
                'value' => ! empty($validated['rounding_enabled']),
            ],
            [
                'category' => 'financial',
                'key' => 'rounding_mode',
                'value' => $validated['rounding_mode'] ?? 'nearest',
            ],
        ];

        $this->settingService->upsertSettings($outlet, $settingsArray, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
