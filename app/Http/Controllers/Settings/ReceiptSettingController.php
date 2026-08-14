<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateReceiptSettingRequest;
use App\Models\Outlet;
use App\Services\Outlet\ManageOutletSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptSettingController extends Controller
{
    public function __construct(
        protected ManageOutletSettingService $settingService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_RECEIPT->value);

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'address', 'phone', 'email', 'logo_url')
            ->orderBy('name')
            ->get();

        $selectedOutletId = $request->get('outlet_id')
            ?? SelectedOutlet::make()->get()?->id
            ?? $outlets->first()?->id;

        $targetOutlet = $outlets->firstWhere('id', $selectedOutletId) ?? $outlets->first();

        $receiptSetting = null;
        if ($targetOutlet) {
            $settingRow = $targetOutlet->settings()
                ->where('category', 'receipt')
                ->where('key', 'layout_config')
                ->first();

            $receiptSetting = $settingRow ? $settingRow->value : null;
        }

        return Inertia::render('Settings/Receipt/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'receiptSetting' => $receiptSetting,
            'business' => $request->user()->business,
        ]);
    }

    public function update(UpdateReceiptSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $outletId = $validated['outlet_id'];
        unset($validated['outlet_id']);

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $this->settingService->upsertSettings($outlet, [
            [
                'category' => 'receipt',
                'key' => 'layout_config',
                'value' => $validated,
            ],
            // Also sync legacy POS receipt format & auto print keys for backward compatibility
            [
                'category' => 'pos',
                'key' => 'receipt_format',
                'value' => $validated['paper_size'] === '80mm' ? 'large' : 'standard',
            ],
            [
                'category' => 'pos',
                'key' => 'auto_print',
                'value' => $validated['auto_print'] ? 1 : 0,
            ],
        ], $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
