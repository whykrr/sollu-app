<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\CreateOutletDeviceRequest;
use App\Http\Requests\Outlet\UpdateOutletDeviceRequest;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Services\Outlet\ManageOutletDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OutletDeviceController extends Controller
{
    public function __construct(
        protected ManageOutletDeviceService $service
    ) {}

    public function store(CreateOutletDeviceRequest $request, Outlet $outlet)
    {
        $this->service->createDevice($outlet, $request->validated(), $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function update(UpdateOutletDeviceRequest $request, Outlet $outlet, OutletDevice $device)
    {
        $this->service->updateDevice($device, $request->validated(), $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function destroy(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $this->service->deleteDevice($device, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function generateOtp(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $otp = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        Cache::put("device_otp_{$otp}", $device->id, now()->addMinutes(5));

        return redirect()->back()->with([
            FlashDataVariable::SUCCESS->value => 'Kode OTP berhasil dibuat',
            'otp_data' => [
                'otp' => $otp,
                'device_id' => $device->id,
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    public function unpair(Request $request, Outlet $outlet, OutletDevice $device)
    {
        $device->tokens()->delete();
        Cache::forget("pos_device_{$device->id}");
        $device->update([
            'client_device_uuid' => null,
            'hardware_fingerprint' => null,
        ]);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Perangkat berhasil diputuskan.'
        );
    }
}
