<?php
namespace App\Services\Outlet;

use App\Models\Outlet;
use App\Models\OutletAuditLog;
use Illuminate\Support\Facades\DB;

class ManageOutletSettingService
{
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

            return $outlet->settings()->get();
        });
    }
}
