<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\Inventory\StockFreezeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OutletFreezeController extends Controller
{
    /**
     * Freeze stock for an outlet.
     */
    public function freeze(Request $request, StockFreezeService $service)
    {
        if (! Gate::check('inventory.opname.freeze') && ! Gate::check('inventory.adjustment.freeze')) {
            abort(403);
        }

        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::findOrFail($request->outlet_id);

        $service->freeze($outlet, Auth::user());

        return redirect()->back()->with('success', 'Stok pada outlet '.$outlet->name.' berhasil dibekukan.');
    }

    /**
     * Unfreeze stock for an outlet.
     */
    public function unfreeze(Request $request, StockFreezeService $service)
    {
        if (! Gate::check('inventory.opname.freeze') && ! Gate::check('inventory.adjustment.freeze')) {
            abort(403);
        }

        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::findOrFail($request->outlet_id);

        $service->unfreeze($outlet, Auth::user());

        return redirect()->back()->with('success', 'Stok pada outlet '.$outlet->name.' berhasil dicairkan.');
    }
}
