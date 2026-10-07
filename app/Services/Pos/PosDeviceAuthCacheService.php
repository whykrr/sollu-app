<?php

namespace App\Services\Pos;

use App\Models\OutletDevice;
use Illuminate\Support\Facades\Cache;

class PosDeviceAuthCacheService
{
    public const CACHE_PREFIX = 'pos_device_';

    public const TTL_DAYS = 7;

    public function putDevice(OutletDevice $device, array $metadata = []): void
    {
        $payload = [
            'device_id' => $device->id,
            'outlet_id' => $device->outlet_id,
            'client_device_uuid' => $metadata['client_device_uuid'] ?? $device->client_device_uuid,
            'hardware_fingerprint' => $metadata['hardware_fingerprint'] ?? $device->hardware_fingerprint,
            'app_version' => $metadata['app_version'] ?? $device->app_version,
            'platform_type' => $metadata['platform_type'] ?? $device->platform_type,
            'is_active' => (bool) $device->is_active,
        ];

        Cache::put(self::CACHE_PREFIX.$device->id, $payload, now()->addDays(self::TTL_DAYS));
    }

    public function getDevice(string $deviceId): ?array
    {
        return Cache::get(self::CACHE_PREFIX.$deviceId);
    }

    public function invalidate(string $deviceId): void
    {
        Cache::forget(self::CACHE_PREFIX.$deviceId);
    }

    public function isDeviceActive(string $deviceId): bool
    {
        $cached = $this->getDevice($deviceId);

        return (bool) ($cached['is_active'] ?? false);
    }
}
