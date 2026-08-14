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
            'is_active' => true,
        ]);

        // Simpan info ke Redis Cache untuk pengecekan cepat di Middleware
        Cache::put("pos_device_{$device->id}", [
            'client_device_uuid' => $deviceUuid,
            'hardware_fingerprint' => $fingerprint,
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
        return $this->successResponse(null, 'Device terkoneksi dan valid.');
    }
}
