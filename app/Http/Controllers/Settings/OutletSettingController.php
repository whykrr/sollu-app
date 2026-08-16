<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\UpdateOutletSettingRequest;
use App\Models\Outlet;
use App\Services\Outlet\ManageOutletSettingService;

class OutletSettingController extends Controller
{
    public function __construct(
        protected ManageOutletSettingService $service
    ) {}

    public function update(UpdateOutletSettingRequest $request, Outlet $outlet)
    {
        $this->service->upsertSettings($outlet, $request->validated('settings'), $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
