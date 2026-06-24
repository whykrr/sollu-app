<?php

namespace App\Http\Controllers\Settings;

use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\CreateOutletDeviceRequest;
use App\Http\Requests\Outlet\UpdateOutletDeviceRequest;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Services\Outlet\ManageOutletDeviceService;
use Illuminate\Http\Request;

class OutletDeviceController extends Controller
{
    public function __construct(
        protected ManageOutletDeviceService $service
    ) {}

    public function store(CreateOutletDeviceRequest $request, Outlet $outlet)
    {
        $this->service->createDevice($outlet, $request->validated(), $request->user());

        return redirect()->back()->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    public function update(UpdateOutletDeviceRequest $request, Outlet $outlet, OutletDevice $device)
    {
        $this->service->updateDevice($device, $request->validated(), $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function destroy(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $this->service->deleteDevice($device, $request->user());

        return redirect()->back()->with('success', ResourceMessage::DELETE_SUCCESS);
    }
}
