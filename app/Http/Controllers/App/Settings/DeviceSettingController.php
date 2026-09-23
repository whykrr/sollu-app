<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Outlet\CreateOutletDeviceRequest;
use App\Http\Requests\App\Outlet\UpdateOutletDeviceRequest;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Services\App\Outlet\ManageOutletDeviceService;
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

        $user = $request->user();
        $businessId = $user->business_id;

        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        $sidebarOutlet = SelectedOutlet::make($user)->get();
        $filterOutletId = $sidebarOutlet ? $sidebarOutlet->id : ($request->get('outlet') ?: $request->get('outlet_id'));

        $query = OutletDevice::query()
            ->with(['outlet:id,name,slug'])
            ->withCount('tokens')
            ->whereHas('outlet', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            });

        if ($filterOutletId) {
            $query->where('outlet_id', $filterOutletId);
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('device_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('outlet', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('device_type')) {
            $query->where('device_type', $request->get('device_type'));
        }

        if ($request->has('is_active') && $request->get('is_active') !== null && $request->get('is_active') !== '') {
            $query->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->sortable($sort, $direction);

        $devices = $query->paginate((int) $request->get('perpage', 12))->withQueryString();

        $business = $user->business;
        $hasMultiDevice = $business ? $business->hasPlanFeature(FeatureEnum::MULTI_DEVICE) : false;

        $outletDeviceCounts = OutletDevice::whereHas('outlet', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
            ->selectRaw('outlet_id, count(*) as count')
            ->groupBy('outlet_id')
            ->pluck('count', 'outlet_id')
            ->toArray();

        return Inertia::render('Settings/Device/Index', [
            'outlets' => $outlets,
            'selectedOutlet' => $sidebarOutlet,
            'devices' => $devices,
            'filters' => [
                'outlet' => $filterOutletId,
                'search' => $request->get('search', ''),
                'device_type' => $request->get('device_type', ''),
                'is_active' => $request->get('is_active', ''),
                'sort' => $sort,
                'direction' => $direction,
            ],
            'hasMultiDevice' => $hasMultiDevice,
            'outletDeviceCounts' => $outletDeviceCounts,
            'otpData' => session('otp_data'),
        ]);
    }

    public function store(CreateOutletDeviceRequest $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $validated = $request->validated();
        $outletId = $validated['outlet_id'];

        $outlet = Outlet::where('business_id', $request->user()->business_id)
            ->findOrFail($outletId);

        $this->service->createDevice($outlet, $validated, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function update(UpdateOutletDeviceRequest $request, OutletDevice $device): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_DEVICE->value);

        $outlet = $device->outlet;
        if (! $outlet || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $this->service->updateDevice($device, $request->validated(), $request->user());

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
