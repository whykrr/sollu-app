<?php

namespace App\Http\Controllers\Dashboard\Product;

use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia(
            'Dashboard/Product/Unit/Index',
            [
                'units' => Unit::globalAndCurrentMerchant()
                    ->filters($request->only(['search', 'status']))
                    ->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))
                    ->paginate($request->get('perpage', 20))
                    ->appends($request->query()),
                'params' => $request->all(),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Product/Unit/Form');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(Unit $unit)
    {
        return inertia('Dashboard/Product/Unit/Form', [
            'returnTo' => url()->previous() != url()->current() ? url()->previous() : null,
            'unit'     => $unit,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitRequest $request)
    {
        Unit::create([
            'merchant_id' => Auth::user()->merchant_id,
            ...$request->validated(),
        ]);

        return redirect()->route('dashboard.products.units.index')
            ->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $unit->update($request->validate());

        return redirect()->to($request->input('return_url') ?? route('dashboard.products.units.index'))
            ->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    /**
     * Soft deletes the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        Gate::authorize('delete', $unit);

        $unit->deleteOrFail();

        return redirect()->route('dashboard.products.units.index')->with('success', ResourceMessage::DELETE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function restore(Unit $unit)
    {
        $unit->restore();

        return redirect()->route('dashboard.products.units.index')->with('success', ResourceMessage::RESTORE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function purge(Unit $unit)
    {
        Gate::authorize('forceDelete', $unit);

        $unit->forceDelete();

        return redirect()->route('dashboard.products.units.index')->with('success', ResourceMessage::PURGE_SUCCESS);
    }
}
