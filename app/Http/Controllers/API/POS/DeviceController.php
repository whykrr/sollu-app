<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\VerifyOtpRequest;
use App\Models\OutletDevice;
use Illuminate\Support\Facades\Cache;

class DeviceController extends Controller
{
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $otp = $request->validated('otp');
        $cacheKey = "device_otp_{$otp}";

        $deviceId = Cache::get($cacheKey);

        if (!$deviceId) {
            return $this->errorResponse('Kode OTP tidak valid atau sudah kadaluarsa.', 400);
        }

        $device = OutletDevice::with(['outlet.business'])->find($deviceId);

        if (!$device) {
            return $this->errorResponse('Perangkat tidak ditemukan.', 404);
        }

        // Hapus OTP setelah berhasil diverifikasi
        Cache::forget($cacheKey);

        // Generate token permanen untuk POS
        $token = $device->createToken('pos-client')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type,
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
            ]
        ], 'Perangkat berhasil dihubungkan.');
    }
}
