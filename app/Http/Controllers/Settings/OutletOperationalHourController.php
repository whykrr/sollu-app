<?php

namespace App\Http\Controllers\Settings;

use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\UpdateOutletOperationalHourRequest;
use App\Models\Outlet;
use App\Services\Outlet\ManageOutletOperationalHourService;

class OutletOperationalHourController extends Controller
{
    public function __construct(
        protected ManageOutletOperationalHourService $service
    ) {}

    public function update(UpdateOutletOperationalHourRequest $request, Outlet $outlet)
    {
        $this->service->upsertHours($outlet, $request->validated('hours'), $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }
}
