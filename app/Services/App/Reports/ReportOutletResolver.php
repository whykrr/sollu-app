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
        $accessibleList = SelectedOutlet::make($user)->getAccessibleOutletsList();
        $accessibleOutletIds = array_map(function ($item) {
            return is_array($item) ? ($item['id'] ?? null) : $item->id;
        }, $accessibleList);
        $accessibleOutletIds = array_values(array_filter($accessibleOutletIds));

        // 1. If explicit outlet is requested, validate accessibility
        if (! empty($requestedOutletId)) {
            if (! in_array($requestedOutletId, $accessibleOutletIds, true)) {
                abort(403, 'Anda tidak memiliki akses ke outlet ini.');
            }

            return [$requestedOutletId];
        }

        // 2. Check active session/sidebar outlet if available
        $selectedOutlet = SelectedOutlet::make($user)->get();
        if ($selectedOutlet && in_array($selectedOutlet->id, $accessibleOutletIds, true)) {
            return [$selectedOutlet->id];
        }

        // 3. Fallback to all accessible outlets for this user
        return $accessibleOutletIds;
    }
}
