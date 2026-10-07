<?php

namespace App\Http\Middleware;

use App\Enums\FeatureEnum;
use App\Models\Business;
use App\Models\OutletDevice;
use App\Services\Pos\PosDeviceAuthCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Pulse\Facades\Pulse;
use Symfony\Component\HttpFoundation\Response;

class VerifyPosDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->user();

        if (! $device || get_class($device) !== OutletDevice::class) {
            return response()->json(['message' => 'Unauthorized device token.'], 401);
        }

        Pulse::rememberUser($device);

        $clientUuid = $request->header('X-DEVICE-UUID');
        $hardwareFingerprint = $request->header('X-HARDWARE-SIGNATURE');

        if (! $clientUuid || ! $hardwareFingerprint) {
            return response()->json(['message' => 'Missing device verification headers.'], 401);
        }

        $cacheService = app(PosDeviceAuthCacheService::class);
        $cachedDevice = $cacheService->getDevice($device->id);

        if (! $cachedDevice) {
            // Rebuild cache if missing
            $cacheService->putDevice($device);
            $cachedDevice = $cacheService->getDevice($device->id);
        }

        if (! ($cachedDevice['is_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat telah dinonaktifkan atau diputus dari outlet.',
                'error_code' => 'DEVICE_UNPAIRED',
            ], 401);
        }

        if ($cachedDevice['client_device_uuid'] !== $clientUuid ||
            $cachedDevice['hardware_fingerprint'] !== $hardwareFingerprint) {
            return response()->json(['message' => 'Device fingerprint mismatch.'], 401);
        }

        // Validate that the business has active subscription/trial granting POS_CASHIER feature
        $outlet = $device->relationLoaded('outlet') ? $device->outlet : $device->outlet()->first();
        $business = $outlet?->business_id ? Business::findCached($outlet->business_id) : null;

        if (! $business || ! $business->hasFeature(FeatureEnum::POS_CASHIER)) {
            return response()->json([
                'message' => 'Masa berlaku langganan atau uji coba toko Anda telah berakhir. Silakan hubungi pemilik usaha untuk memperpanjang paket.',
                'error_code' => 'SUBSCRIPTION_EXPIRED',
            ], 402);
        }

        // Pastikan team ID permission diset ke business_id saat mengakses API POS
        setPermissionsTeamId($business->id);

        return $next($request);
    }
}
