<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\ConnectDeviceRequest;
use App\Models\OutletDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeviceController extends Controller
{
    public function connect(ConnectDeviceRequest $request)
    {
        $otp = $request->validated('otp');
        $deviceUuid = $request->validated('device_uuid');
        $fingerprint = $request->validated('hardware_fingerprint');
        $appVersion = $request->validated('app_version');
        $platformType = $request->validated('platform_type');

        $cacheKey = "device_otp_{$otp}";
        $deviceId = Cache::get($cacheKey);

        if (! $deviceId) {
            return $this->errorResponse('Kode OTP tidak valid atau sudah kadaluarsa.', [], 400);
        }

        $device = OutletDevice::with(['outlet.business'])->find($deviceId);

        if (! $device) {
            return $this->errorResponse('Perangkat tidak ditemukan.', [], 404);
        }

        // Hapus OTP setelah berhasil diverifikasi
        Cache::forget($cacheKey);

        // Simpan device_uuid dan hardware_fingerprint ke DB
        $device->update([
            'client_device_uuid' => $deviceUuid,
            'hardware_fingerprint' => $fingerprint,
            'app_version' => $appVersion,
            'platform_type' => $platformType,
            'is_active' => true,
        ]);

        // Simpan info ke Redis Cache untuk pengecekan cepat di Middleware
        Cache::put("pos_device_{$device->id}", [
            'client_device_uuid' => $deviceUuid,
            'hardware_fingerprint' => $fingerprint,
            'app_version' => $appVersion,
            'platform_type' => $platformType,
            'is_active' => true,
        ], now()->addDays(7));

        // Generate token permanen untuk POS
        $token = $device->createToken('pos-client')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'device' => [
                'id' => $device->id,
                'name' => $device->device_name,
                'type' => $device->device_type,
            ],
            'outlet' => [
                'id' => $device->outlet->id,
                'name' => $device->outlet->name,
                'address' => $device->outlet->address,
                'phone_number' => $device->outlet->phone_number,
            ],
            'business' => [
                'id' => $device->outlet->business->id,
                'name' => $device->outlet->business->name,
            ],
        ], 'Perangkat berhasil dihubungkan.');
    }

    public function checkStatus(Request $request)
    {
        // Jika sudah lolos Middleware VerifyPosDevice, artinya device valid & aktif
        $device = $request->user();
        if ($device && ($request->filled('app_version') || $request->filled('platform_type'))) {
            $device->update([
                'app_version' => $request->input('app_version', $device->app_version),
                'platform_type' => $request->input('platform_type', $device->platform_type),
            ]);

            $cacheKey = "pos_device_{$device->id}";
            $cached = Cache::get($cacheKey, []);
            $cached['app_version'] = $device->app_version;
            $cached['platform_type'] = $device->platform_type;
            Cache::put($cacheKey, $cached, now()->addDays(7));
        }

        return $this->successResponse(null, 'Device terkoneksi dan valid.');
    }
}
