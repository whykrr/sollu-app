<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\InventoryCostingMethod;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\UpdateInventoryCostingMethodRequest;
use App\Http\Requests\App\Settings\UpdateInventorySodSettingRequest;
use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Inventory\InventorySodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventorySettingController extends Controller
{
    public function __construct(
        protected InventoryCostingService $costingService,
        protected InventorySodService $sodService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_VIEW->value);

        /** @var Business */
        $business = Auth::user()->business;

        $trackedItemsCount = InventoryItem::where('business_id', $business->id)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->count();

        $totalStockValue = (float) InventoryBalance::where('business_id', $business->id)
            ->sum('total_value');

        return Inertia::render('Settings/Inventory/Index', [
            'costingMethod' => $business->getCostingMethod()->value,
            'isConfigured' => $business->isCostingMethodConfigured(),
            'options' => InventoryCostingMethod::options(),
            'sodSettings' => $business->getInventorySodSettings(),
            'stats' => [
                'tracked_items_count' => $trackedItemsCount,
                'total_stock_value' => $totalStockValue,
            ],
        ]);
    }

    public function update(UpdateInventoryCostingMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $newMethod = InventoryCostingMethod::from($validated['costing_method']);

        /** @var Business */
        $business = Auth::user()->business;

        $this->costingService->switchCostingMethod($business, $newMethod);

        SummaryUser::cacheDelete($request->user()->id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function updateSod(UpdateInventorySodSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Business */
        $business = Auth::user()->business;

        $settings = $business->settings ?? [];
        $settings['inventory_sod'] = [
            'enabled' => $validated['enabled'],
            'allow_owner_bypass' => $validated['allow_owner_bypass'],
            'rules' => $validated['rules'] ?? [],
        ];

        $business->settings = $settings;
        $business->save();

        SummaryUser::cacheDelete($request->user()->id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
