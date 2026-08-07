<?php

namespace App\Services\Inventory;

use App\Models\Outlet;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockFreezeService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Freeze stock for a specific outlet.
     */
    public function freeze(Outlet $outlet, User $user): Outlet
    {
        return DB::transaction(function () use ($outlet, $user) {
            $outlet->update(['is_stock_frozen' => true]);

            $this->activityLogService->log(
                $outlet,
                'frozen',
                $user,
                ['message' => 'Stok outlet dibekukan']
            );

            return $outlet;
        });
    }

    /**
     * Unfreeze stock for a specific outlet.
     */
    public function unfreeze(Outlet $outlet, User $user): Outlet
    {
        return DB::transaction(function () use ($outlet, $user) {
            $outlet->update(['is_stock_frozen' => false]);

            $this->activityLogService->log(
                $outlet,
                'unfrozen',
                $user,
                ['message' => 'Stok outlet dicairkan']
            );

            return $outlet;
        });
    }

    /**
     * Assert that the stock for a specific outlet is not frozen.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function assertNotFrozen(Outlet $outlet): void
    {
        if ($outlet->is_stock_frozen) {
            abort(403, "Stok outlet '{$outlet->name}' sedang dibekukan. Hubungi admin.");
        }
    }
}
