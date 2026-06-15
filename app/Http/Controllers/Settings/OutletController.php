<?php

namespace App\Http\Controllers\Settings;

use App\Constants\ResourceMessage;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\CreateOutletRequest;
use App\Http\Requests\Outlet\UpdateOutletRequest;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req, Outlet $outlet = null)
    {
        $outlets = Outlet::currentBusiness()
            ->sortable($req->get('sort', 'created_at'), $req->get('direction', 'asc'))
            ->paginate($req->get('perpage', 20))
            ->appends($req->query());

        return inertia('Settings/Outlet/Index', [
            'outlets' => $outlets,
            'params'  => $req->all(),
            'outlet'  => $outlet,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOutletRequest $req)
    {

        $outlet              = new Outlet();
        $outlet->business_id = $req->user()->business_id;
        $outlet->name        = $req->validated('name');
        $outlet->address     = $req->validated('address');
        $outlet->is_active   = false;
        $outlet->save();


        if ($req->user()->is_root_user) {
            $req->user()->outlets()->attach($outlet->id);
        } else {
            $root_user = User::currentBusiness()->where('is_root_user', true)->first();
            if ($root_user) {
                $root_user->outlets()->attach($outlet->id);
            }
        }


        return redirect()->back()->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutletRequest $request, Outlet $outlet)
    {
        $outlet->name    = $request->validated('name');
        $outlet->address = $request->validated('address');
        $outlet->save();

        $request->user()->outlets()->attach($outlet->id);

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function disabled(Outlet $outlet)
    {
        $outlet->is_active = false;
        $outlet->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function enabled(Outlet $outlet)
    {
        $outlet->is_active = true;
        $outlet->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }
}
