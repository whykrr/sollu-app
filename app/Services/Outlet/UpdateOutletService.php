<?php

namespace App\Services\Outlet;

use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOutletService
{
    public function execute(Outlet $outlet, array $data, User $user): Outlet
    {
        return DB::transaction(function () use ($outlet, $data, $user) {
            $oldData = $outlet->toArray();

            $outlet->update($data);

            // Audit log
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'metadata' => ['old' => $oldData, 'new' => $data],
            ]);

            return $outlet;
        });
    }
}
