<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOperationalSettingRequest;
use App\Models\Outlet;
use App\Services\Outlet\ManageOutletOperationalHourService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperationalSettingController extends Controller
{
    public function __construct(
        protected ManageOutletOperationalHourService $service
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::OUTLET_VIEW->value);

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'timezone')
            ->orderBy('name')
            ->get();

        $selectedOutletId = $request->get('outlet_id')
            ?? SelectedOutlet::make()->get()?->id
            ?? $outlets->first()?->id;

        $targetOutlet = $outlets->firstWhere('id', $selectedOutletId) ?? $outlets->first();

        $hours = [];
        if ($targetOutlet) {
            $hours = $targetOutlet->operationalHours()
                ->orderBy('day_of_week')
                ->get();
        }

        return Inertia::render('Settings/Operational/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'operationalHours' => $hours,
        ]);
    }

    public function update(UpdateOperationalSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $outletId = $validated['outlet_id'];

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $this->service->upsertHours($outlet, $validated['hours'], $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
