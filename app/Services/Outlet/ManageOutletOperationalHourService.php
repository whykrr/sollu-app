<?php

namespace App\Services\Outlet;

use App\Models\Outlet;
use App\Models\OutletAuditLog;
use Illuminate\Support\Facades\DB;

class ManageOutletOperationalHourService
{
    public function upsertHours(Outlet $outlet, array $hours, $user)
    {
        return DB::transaction(function () use ($outlet, $hours, $user) {
            foreach ($hours as $hour) {
                $outlet->operationalHours()->updateOrCreate(
                    [
                        'day_of_week' => $hour['day_of_week'],
                    ],
                    [
                        'open_time' => $hour['open_time'] ?? null,
                        'close_time' => $hour['close_time'] ?? null,
                        'is_closed' => $hour['is_closed'],
                    ]
                );
            }

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'operational_hours_updated',
                'metadata' => ['hours' => $hours],
            ]);

            return $outlet->operationalHours()->get();
        });
    }
}
