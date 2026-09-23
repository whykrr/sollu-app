<?php

namespace App\Services\App\Outlet;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\FeatureEnum;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\OutletDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageOutletDeviceService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function createDevice(Outlet $outlet, array $data, $user)
    {
        $business = $outlet->business ?? $user->business;
        if ($business && ! $business->hasPlanFeature(FeatureEnum::MULTI_DEVICE)) {
            $existingDeviceCount = $outlet->devices()->count();
            if ($existingDeviceCount >= 1) {
                throw ValidationException::withMessages([
                    'outlet_id' => 'Paket tokomu saat ini hanya mendukung 1 perangkat per outlet. Yuk, tingkatkan ke paket Pro untuk menghubungkan banyak perangkat kasir!',
                ]);
            }
        }

        return DB::transaction(function () use ($outlet, $data, $user) {
            $deviceType = $data['device_type'] instanceof \App\Enums\DeviceTypeEnum
                ? $data['device_type']->value
                : $data['device_type'];

            $device = $outlet->devices()->create([
                'device_name' => $data['device_name'],
                'device_type' => $deviceType,
                'serial_number' => $data['serial_number'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'device_added',
                'metadata' => ['device' => $device->toArray()],
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'device.created',
                description: "Menghubungkan perangkat kasir baru: {$device->device_name}",
                subject: $device,
                causer: $user,
                businessId: $outlet->business_id,
                outletId: $outlet->id,
                properties: [
                    'device' => $device->toArray(),
                ]
            );

            return $device;
        });
    }

    public function updateDevice(OutletDevice $device, array $data, $user)
    {
        if (isset($data['outlet_id']) && $data['outlet_id'] !== $device->outlet_id) {
            $targetOutlet = Outlet::where('business_id', $user->business_id)->findOrFail($data['outlet_id']);
            $business = $targetOutlet->business ?? $user->business;
            if ($business && ! $business->hasPlanFeature(FeatureEnum::MULTI_DEVICE)) {
                $existingCount = $targetOutlet->devices()->where('id', '!=', $device->id)->count();
                if ($existingCount >= 1) {
                    throw ValidationException::withMessages([
                        'outlet_id' => 'Outlet tujuan sudah memiliki 1 perangkat kasir. Paket tokomu saat ini hanya mendukung 1 perangkat per outlet.',
                    ]);
                }
            }
        }

        if (isset($data['device_type']) && $data['device_type'] instanceof \App\Enums\DeviceTypeEnum) {
            $data['device_type'] = $data['device_type']->value;
        }

        return DB::transaction(function () use ($device, $data, $user) {
            $oldData = $device->toArray();
            $device->update($data);

            OutletAuditLog::create([
                'outlet_id' => $device->outlet_id,
                'user_id' => $user->id,
                'action' => 'device_updated',
                'metadata' => ['old' => $oldData, 'new' => $data],
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'device.updated',
                description: "Memperbarui data perangkat kasir: {$device->device_name}",
                subject: $device,
                causer: $user,
                businessId: $device->outlet?->business_id ?? $user->business_id,
                outletId: $device->outlet_id,
                properties: [
                    'old' => $oldData,
                    'new' => $data,
                ]
            );

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

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'device.deleted',
                description: "Menghapus perangkat kasir: {$device->device_name}",
                subject: $device,
                causer: $user,
                businessId: $device->outlet?->business_id ?? $user->business_id,
                outletId: $device->outlet_id
            );

            $device->tokens()->delete();
            \Illuminate\Support\Facades\Cache::forget("pos_device_{$device->id}");

            $device->delete();
        });
    }
}
