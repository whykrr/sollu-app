<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\UpdateSalesSettingRequest;
use App\Models\Outlet;
use App\Services\App\Outlet\ManageOutletSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesSettingController extends Controller
{
    public function __construct(
        protected ManageOutletSettingService $settingService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_SALES->value);

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        $selectedOutletId = SelectedOutlet::resolveEffectiveOutletId($request->user(), $request->get('outlet_id'))
            ?? $outlets->first()?->id;

        $targetOutlet = $outlets->firstWhere('id', $selectedOutletId) ?? $outlets->first();

        $salesSettings = [
            'allow_negative_stock_b2b' => false,
            'allow_custom_price_b2b' => true,
            'default_due_days_b2b' => 14,
            'b2b_invoice_prefix' => 'INV',
        ];

        $posSettings = [
            'enable_supervisor_pin' => false,
            'allow_negative_stock' => true,
            'pos_order_types' => ['walk_in', 'dine_in', 'take_away'],
        ];

        $devices = [];

        if ($targetOutlet) {
            $settings = $targetOutlet->settings()
                ->whereIn('category', ['sales', 'pos'])
                ->get();

            foreach ($settings as $setting) {
                if ($setting->category === 'pos') {
                    if ($setting->key === 'enable_supervisor_pin') {
                        $posSettings['enable_supervisor_pin'] = (bool) $setting->value;
                    } elseif ($setting->key === 'pos_order_types') {
                        $posSettings['pos_order_types'] = is_array($setting->value) ? $setting->value : ['walk_in', 'dine_in', 'take_away'];
                    }
                } elseif ($setting->category === 'sales') {
                    if ($setting->key === 'allow_negative_stock') {
                        $posSettings['allow_negative_stock'] = (bool) $setting->value;
                    } elseif ($setting->key === 'allow_negative_stock_b2b' || $setting->key === 'allow_custom_price_b2b') {
                        $salesSettings[$setting->key] = (bool) $setting->value;
                    } elseif ($setting->key === 'default_due_days_b2b') {
                        $salesSettings[$setting->key] = (int) $setting->value;
                    } elseif ($setting->key === 'b2b_invoice_prefix') {
                        $salesSettings[$setting->key] = (string) ($setting->value ?? 'INV');
                    }
                }
            }

            $devices = $targetOutlet->devices()
                ->withCount('tokens')
                ->latest()
                ->get();
        }

        return Inertia::render('Settings/Sales/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'salesSettings' => $salesSettings,
            'posSettings' => $posSettings,
            'devices' => $devices,
        ]);
    }

    public function update(UpdateSalesSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $outletId = $validated['outlet_id'];

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $settingsArray = [];

        // Save POS settings if present
        if (array_key_exists('enable_supervisor_pin', $validated)) {
            $settingsArray[] = [
                'category' => 'pos',
                'key' => 'enable_supervisor_pin',
                'value' => (bool) $validated['enable_supervisor_pin'],
            ];
        }

        if (array_key_exists('allow_negative_stock', $validated)) {
            $settingsArray[] = [
                'category' => 'sales',
                'key' => 'allow_negative_stock',
                'value' => (bool) $validated['allow_negative_stock'],
            ];
        }

        if (array_key_exists('pos_order_types', $validated)) {
            $settingsArray[] = [
                'category' => 'pos',
                'key' => 'pos_order_types',
                'value' => array_values($validated['pos_order_types']),
            ];
        }

        // Save B2B Invoice settings if present
        if (array_key_exists('allow_negative_stock_b2b', $validated)) {
            $settingsArray[] = [
                'category' => 'sales',
                'key' => 'allow_negative_stock_b2b',
                'value' => (bool) $validated['allow_negative_stock_b2b'],
            ];
        }

        if (array_key_exists('allow_custom_price_b2b', $validated)) {
            $settingsArray[] = [
                'category' => 'sales',
                'key' => 'allow_custom_price_b2b',
                'value' => (bool) $validated['allow_custom_price_b2b'],
            ];
        }

        if (array_key_exists('default_due_days_b2b', $validated)) {
            $settingsArray[] = [
                'category' => 'sales',
                'key' => 'default_due_days_b2b',
                'value' => (int) $validated['default_due_days_b2b'],
            ];
        }

        if (array_key_exists('b2b_invoice_prefix', $validated)) {
            $settingsArray[] = [
                'category' => 'sales',
                'key' => 'b2b_invoice_prefix',
                'value' => strtoupper(trim($validated['b2b_invoice_prefix'] ?? 'INV')),
            ];
        }

        if (! empty($settingsArray)) {
            $this->settingService->upsertSettings($outlet, $settingsArray, $request->user());
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
