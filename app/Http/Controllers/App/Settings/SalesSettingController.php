<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\UpdateSalesSettingRequest;
use App\Models\Outlet;
use App\Services\App\Outlet\ManageOutletSettingService;
use Illuminate\Auth\Access\AuthorizationException;
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
        $user = $request->user();
        if (! $user->can(PermissionEnum::SETTING_SALES->value)
            && ! $user->can(PermissionEnum::SETTING_TAX->value)
            && ! $user->can(PermissionEnum::SETTING_RECEIPT->value)) {
            throw new AuthorizationException;
        }

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'address', 'phone', 'email', 'logo_url')
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

        $taxSettings = [
            'financial_tax' => 0.0,
            'financial_service_fee' => 0.0,
            'tax_included_in_price' => false,
            'rounding_enabled' => false,
            'rounding_mode' => 'nearest',
        ];

        $receiptSetting = null;

        if ($targetOutlet) {
            $settings = $targetOutlet->settings()
                ->whereIn('category', ['sales', 'pos', 'financial', 'receipt'])
                ->get();

            foreach ($settings as $setting) {
                if ($setting->category === 'pos') {
                    if ($setting->key === 'enable_supervisor_pin') {
                        $posSettings['enable_supervisor_pin'] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->key === 'pos_order_types') {
                        $posSettings['pos_order_types'] = is_array($setting->value) ? $setting->value : ['walk_in', 'dine_in', 'take_away'];
                    }
                } elseif ($setting->category === 'sales') {
                    if ($setting->key === 'allow_negative_stock') {
                        $posSettings['allow_negative_stock'] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->key === 'allow_negative_stock_b2b' || $setting->key === 'allow_custom_price_b2b') {
                        $salesSettings[$setting->key] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->key === 'default_due_days_b2b') {
                        $salesSettings[$setting->key] = (int) $setting->value;
                    } elseif ($setting->key === 'b2b_invoice_prefix') {
                        $salesSettings[$setting->key] = (string) ($setting->value ?? 'INV');
                    }
                } elseif ($setting->category === 'financial') {
                    if ($setting->key === 'tax') {
                        $taxSettings['financial_tax'] = (float) $setting->value;
                    } elseif ($setting->key === 'service_fee') {
                        $taxSettings['financial_service_fee'] = (float) $setting->value;
                    } elseif ($setting->key === 'tax_included_in_price') {
                        $taxSettings['tax_included_in_price'] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->key === 'rounding_enabled') {
                        $taxSettings['rounding_enabled'] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($setting->key === 'rounding_mode') {
                        $taxSettings['rounding_mode'] = (string) ($setting->value ?? 'nearest');
                    }
                } elseif ($setting->category === 'receipt') {
                    if ($setting->key === 'layout_config') {
                        $receiptSetting = $setting->value;
                    }
                }
            }
        }

        $requestedTab = $request->query('tab');
        $initialTab = 'pos';
        if ($requestedTab && in_array($requestedTab, ['pos', 'tax', 'receipt', 'b2b'], true)) {
            $initialTab = $requestedTab;
        } elseif (! $user->can(PermissionEnum::SETTING_SALES->value)) {
            if ($user->can(PermissionEnum::SETTING_TAX->value)) {
                $initialTab = 'tax';
            } elseif ($user->can(PermissionEnum::SETTING_RECEIPT->value)) {
                $initialTab = 'receipt';
            }
        }

        return Inertia::render('Settings/Sales/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'salesSettings' => $salesSettings,
            'posSettings' => $posSettings,
            'taxSettings' => $taxSettings,
            'receiptSetting' => $receiptSetting,
            'business' => $request->user()->business,
            'initialTab' => $initialTab,
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
            $isSupervisorPin = (bool) $validated['enable_supervisor_pin'];
            if ($isSupervisorPin && ! $outlet->business?->hasFeature(FeatureEnum::DOUBLE_AUTHORIZATION)) {
                abort(403, 'Fitur PIN Supervisor memerlukan paket langganan yang mendukung Otorisasi Ganda.');
            }
            $settingsArray[] = [
                'category' => 'pos',
                'key' => 'enable_supervisor_pin',
                'value' => $isSupervisorPin,
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
