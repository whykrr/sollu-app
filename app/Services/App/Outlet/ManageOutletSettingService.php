<?php

namespace App\Services\App\Outlet;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use Illuminate\Support\Facades\DB;

class ManageOutletSettingService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function upsertSettings(Outlet $outlet, array $settings, $user)
    {
        return DB::transaction(function () use ($outlet, $settings, $user) {
            foreach ($settings as $setting) {
                $outlet->settings()->updateOrCreate(
                    [
                        'category' => $setting['category'],
                        'key' => $setting['key'],
                    ],
                    [
                        'value' => $setting['value'],
                    ]
                );
            }

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'settings_updated',
                'metadata' => ['settings' => $settings],
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet_settings.updated',
                description: "Memperbarui konfigurasi cabang: {$outlet->name}",
                subject: $outlet,
                causer: $user,
                businessId: $outlet->business_id,
                outletId: $outlet->id,
                properties: [
                    'settings' => $settings,
                ]
            );

            return $outlet->settings()->get();
        });
    }
}
