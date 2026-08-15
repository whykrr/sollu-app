<?php

namespace App\Services\Outlet;

use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\OutletDevice;
use Illuminate\Support\Facades\DB;

class ManageOutletDeviceService
{
    public function createDevice(Outlet $outlet, array $data, $user)
    {
        return DB::transaction(function () use ($outlet, $data, $user) {
            $device = $outlet->devices()->create([
                'device_name' => $data['device_name'],
                'device_type' => $data['device_type'],
                'serial_number' => $data['serial_number'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'device_added',
                'metadata' => ['device' => $device->toArray()],
            ]);

            return $device;
        });
    }

    public function updateDevice(OutletDevice $device, array $data, $user)
    {
        return DB::transaction(function () use ($device, $data, $user) {
            $oldData = $device->toArray();
            $device->update($data);

            OutletAuditLog::create([
                'outlet_id' => $device->outlet_id,
                'user_id' => $user->id,
                'action' => 'device_updated',
                'metadata' => ['old' => $oldData, 'new' => $data],
            ]);

            return $device;
        });
    }

    public function deleteDevice(OutletDevice $device, $user)
    {
        return DB::transaction(function () use ($device, $user) {
            OutletAuditLog::create([
                'outlet_id' => $device->outlet_id,
                'user_id' => $user->id,
                'action' => 'device_deleted',
                'metadata' => ['device' => $device->toArray()],
            ]);

            $device->tokens()->delete();
            \Illuminate\Support\Facades\Cache::forget("pos_device_{$device->id}");

            $device->delete();
        });
    }
}
