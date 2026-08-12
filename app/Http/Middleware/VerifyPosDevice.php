<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class VerifyPosDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->user();

        if (!$device || get_class($device) !== \App\Models\OutletDevice::class) {
            return response()->json(['message' => 'Unauthorized device token.'], 401);
        }

        $clientUuid = $request->header('X-DEVICE-UUID');
        $hardwareFingerprint = $request->header('X-HARDWARE-SIGNATURE');

        if (!$clientUuid || !$hardwareFingerprint) {
            return response()->json(['message' => 'Missing device verification headers.'], 401);
        }

        $cacheKey = "pos_device_{$device->id}";
        $cachedDevice = Cache::get($cacheKey);

        if (!$cachedDevice) {
            // Rebuild cache if missing
            $cachedDevice = [
                'client_device_uuid' => $device->client_device_uuid,
                'hardware_fingerprint' => $device->hardware_fingerprint,
                'is_active' => $device->is_active,
            ];
            Cache::put($cacheKey, $cachedDevice, now()->addDays(7));
        }

        if (!$cachedDevice['is_active']) {
            return response()->json(['message' => 'Device is deactivated.'], 401);
        }

        if ($cachedDevice['client_device_uuid'] !== $clientUuid || 
            $cachedDevice['hardware_fingerprint'] !== $hardwareFingerprint) {
            return response()->json(['message' => 'Device fingerprint mismatch.'], 401);
        }

        return $next($request);
    }
}
