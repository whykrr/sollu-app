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

    public function generateOtp(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $otp = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        
        \Illuminate\Support\Facades\Cache::put("device_otp_{$otp}", $device->id, now()->addMinutes(5));

        return redirect()->back()->with([
            'success' => "Kode OTP berhasil dibuat",
            'otp_data' => [
                'otp' => $otp,
                'device_id' => $device->id,
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
            ]
        ]);
    }

    public function unpair(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $device->tokens()->delete();

        return redirect()->back()->with('success', 'Perangkat berhasil diputuskan.');
    }
}
