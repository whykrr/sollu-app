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

        $defaultTnc = "1. Pembayaran dilakukan sesuai tanggal jatuh tempo yang tertera pada faktur.\n2. Pembayaran via transfer ditujukan ke rekening resmi yang tertera.\n3. Barang yang sudah diterima dalam kondisi baik tidak dapat dikembalikan tanpa persetujuan tertulis.";

        $salesSettings = [
            'allow_negative_stock_b2b' => false,
            'allow_custom_price_b2b' => true,
            'sales_channels_b2b' => ['direct', 'wholesale', 'e_commerce', 'social_media'],
            'default_due_days_b2b' => 14,
            'default_terms_and_conditions_b2b' => $defaultTnc,
            'b2b_invoice_prefix' => 'INV',
        ];

        if ($targetOutlet) {
            $settings = $targetOutlet->settings()
                ->where('category', 'sales')
                ->whereIn('key', [
                    'allow_negative_stock_b2b',
                    'allow_custom_price_b2b',
                    'sales_channels_b2b',
                    'default_due_days_b2b',
                    'default_terms_and_conditions_b2b',
                    'b2b_invoice_prefix',
                ])
                ->get();

            foreach ($settings as $setting) {
                if ($setting->key === 'allow_negative_stock_b2b' || $setting->key === 'allow_custom_price_b2b') {
                    $salesSettings[$setting->key] = (bool) $setting->value;
                } elseif ($setting->key === 'default_due_days_b2b') {
                    $salesSettings[$setting->key] = (int) $setting->value;
                } elseif ($setting->key === 'sales_channels_b2b') {
                    $salesSettings[$setting->key] = is_array($setting->value) ? $setting->value : ['direct', 'wholesale', 'e_commerce', 'social_media'];
                } elseif ($setting->key === 'default_terms_and_conditions_b2b') {
                    $salesSettings[$setting->key] = (string) ($setting->value ?? '');
                } elseif ($setting->key === 'b2b_invoice_prefix') {
                    $salesSettings[$setting->key] = (string) ($setting->value ?? 'INV');
                }
            }
        }

        return Inertia::render('Settings/Sales/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'salesSettings' => $salesSettings,
        ]);
    }

    public function update(UpdateSalesSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $outletId = $validated['outlet_id'];

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $settingsArray = [
            [
                'category' => 'sales',
                'key' => 'allow_negative_stock_b2b',
                'value' => (bool) $validated['allow_negative_stock_b2b'],
            ],
            [
                'category' => 'sales',
                'key' => 'allow_custom_price_b2b',
                'value' => (bool) $validated['allow_custom_price_b2b'],
            ],
            [
                'category' => 'sales',
                'key' => 'sales_channels_b2b',
                'value' => array_values($validated['sales_channels_b2b']),
            ],
            [
                'category' => 'sales',
                'key' => 'default_due_days_b2b',
                'value' => (int) $validated['default_due_days_b2b'],
            ],
            [
                'category' => 'sales',
                'key' => 'default_terms_and_conditions_b2b',
                'value' => $validated['default_terms_and_conditions_b2b'] ?? '',
            ],
            [
                'category' => 'sales',
                'key' => 'b2b_invoice_prefix',
                'value' => strtoupper(trim($validated['b2b_invoice_prefix'] ?? 'INV')),
            ],
        ];

        $this->settingService->upsertSettings($outlet, $settingsArray, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
