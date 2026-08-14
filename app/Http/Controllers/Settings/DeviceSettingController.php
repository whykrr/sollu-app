<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Services\Outlet\ManageOutletDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DeviceSettingController extends Controller
{
    public function __construct(
        protected ManageOutletDeviceService $service
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $businessId = $request->user()->business_id;
        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        $selectedOutletId = $request->get('outlet_id')
            ?? SelectedOutlet::make()->get()?->id
            ?? $outlets->first()?->id;

        $targetOutlet = $outlets->firstWhere('id', $selectedOutletId) ?? $outlets->first();

        $devices = [];
        if ($targetOutlet) {
            $devices = OutletDevice::where('outlet_id', $targetOutlet->id)
                ->withCount('tokens')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('Settings/Device/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $targetOutlet,
            'devices' => $devices,
            'otpData' => session('otp_data'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $validated = $request->validate([
            'outlet_id' => ['required', 'uuid'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($validated['outlet_id']);

        $this->service->createDevice($outlet, $validated, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function update(Request $request, OutletDevice $device): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $outlet = $device->outlet;
        if (! $outlet || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $this->service->updateDevice($device, $validated, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function destroy(Request $request, OutletDevice $device): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $outlet = $device->outlet;
        if (! $outlet || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $this->service->deleteDevice($device, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function generateOtp(Request $request, OutletDevice $device): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $outlet = $device->outlet;
        if (! $outlet || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

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

    public function unpair(Request $request, OutletDevice $device): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $outlet = $device->outlet;
        if (! $outlet || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

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
