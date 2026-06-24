<?php

namespace App\Services\Outlet;

use App\Helpers\SummaryUser;
use App\Models\Outlet;
use App\Models\User;
use App\Models\OutletAuditLog;
use Illuminate\Support\Facades\DB;

class ManageOutletStatusService
{
    public function toggleStatus(Outlet $outlet, bool $isActive, User $user): Outlet
    {
        $outlet->is_active = $isActive;
        $outlet->save();

        SummaryUser::cacheDelete();

        $action = $isActive ? 'enabled' : 'disabled';
        OutletAuditLog::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'action' => $action,
        ]);

        return $outlet;
    }

    public function delete(Outlet $outlet, User $user): void
    {
        DB::transaction(function () use ($outlet, $user) {
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'deleted',
            ]);
            $outlet->delete();
            SummaryUser::cacheDelete();
        });
    }

    public function restore(string $outletId, User $user): Outlet
    {
        return DB::transaction(function () use ($outletId, $user) {
            $outlet = Outlet::withTrashed()->where('id', $outletId)->firstOrFail();
            $outlet->restore();

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'restored',
            ]);
            SummaryUser::cacheDelete();

            return $outlet;
        });
    }
}
