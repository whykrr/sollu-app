<?php

namespace App\Http\Middleware;

use App\Models\Inventory\StockAdjustment;
use App\Models\Outlet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockNotFrozen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $outletId = $this->getOutletId($request);

        if ($outletId) {
            $outlet = Outlet::find($outletId);
            if ($outlet && $outlet->is_stock_frozen) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Stok outlet ini sedang dibekukan. Hubungi admin untuk mencairkan.',
                    ], 422);
                }

                return back()->with('failed', 'Stok outlet ini sedang dibekukan. Hubungi admin untuk mencairkan.');
            }
        }

        return $next($request);
    }

    protected function getOutletId(Request $request): ?string
    {
        // 1. From route parameter for Stock Adjustment (Approve / Reject / Void)
        $adjustment = $request->route('stock_adjustment');
        if ($adjustment instanceof StockAdjustment) {
            return $adjustment->outlet_id;
        } elseif (is_string($adjustment)) {
            $model = StockAdjustment::find($adjustment);
            if ($model) {
                return $model->outlet_id;
            }
        }

        // 2. From request body (Store Adjustment)
        if ($request->has('outlet_id')) {
            return $request->input('outlet_id');
        }

        return null;
    }
}
