<?php

namespace App\Services\App\Reports;

use App\Helpers\SelectedOutlet;
use App\Models\Outlet;
use App\Models\User;

class ReportOutletResolver
{
    /**
     * Resolve target outlet IDs based on user permissions and requested / session outlet.
     *
     * @return array<string>
     */
    public static function resolve(User $user, ?string $requestedOutletId = null): array
    {
        $businessId = $user->business_id;

        /** @var array<string> $accessibleOutletIds */
        $accessibleOutletIds = $user->is_root_user
            ? Outlet::query()
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray()
            : $user->outlets()
                ->where('outlets.is_active', true)
                ->where('outlets.business_id', $businessId)
                ->pluck('outlets.id')
                ->toArray();

        // 1. If explicit outlet is requested, validate accessibility
        if (! empty($requestedOutletId)) {
            if (! in_array($requestedOutletId, $accessibleOutletIds, true)) {
                abort(403, 'Anda tidak memiliki akses ke outlet ini.');
            }

            return [$requestedOutletId];
        }

        // 2. Check active session outlet if available
        $selectedOutlet = SelectedOutlet::make($user)->get();
        if ($selectedOutlet && in_array($selectedOutlet->id, $accessibleOutletIds, true)) {
            return [$selectedOutlet->id];
        }

        // 3. Fallback to all accessible outlets for this user
        return $accessibleOutletIds;
    }
}
