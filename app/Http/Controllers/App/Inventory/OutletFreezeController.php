<?php

namespace App\Http\Controllers\App\Inventory;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\App\Inventory\StockFreezeService;
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
        if (! Gate::check(PermissionEnum::INVENTORY_OPNAME_VALIDATE->value) && ! Gate::check(PermissionEnum::INVENTORY_ADJUST_VALIDATE->value)) {
            abort(403, 'Kamu tidak memiliki wewenang untuk membekukan atau mencairkan stok outlet.');
        }

        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::query()->currentBusiness()->findOrFail($request->outlet_id);

        $service->freeze($outlet, Auth::user());

        return redirect()->back()->with('success', 'Stok pada outlet '.$outlet->name.' berhasil dibekukan.');
    }

    /**
     * Unfreeze stock for an outlet.
     */
    public function unfreeze(Request $request, StockFreezeService $service)
    {
        if (! Gate::check(PermissionEnum::INVENTORY_OPNAME_VALIDATE->value) && ! Gate::check(PermissionEnum::INVENTORY_ADJUST_VALIDATE->value)) {
            abort(403, 'Kamu tidak memiliki wewenang untuk membekukan atau mencairkan stok outlet.');
        }

        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::query()->currentBusiness()->findOrFail($request->outlet_id);

        $service->unfreeze($outlet, Auth::user());

        return redirect()->back()->with('success', 'Stok pada outlet '.$outlet->name.' berhasil dicairkan.');
    }
}
