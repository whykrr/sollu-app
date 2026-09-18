<?php

namespace App\Http\Controllers\App\Outlet;

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchOutletController extends Controller
{
    /**
     * Switch the active outlet context.
     */
    public function switch(Request $request, string $id): RedirectResponse
    {
        SelectedOutlet::make($request->user())->change($id);

        return redirect()->back();
    }

    /**
     * Reset the active outlet context to all outlets.
     */
    public function all(Request $request): RedirectResponse
    {
        SelectedOutlet::make($request->user())->all();

        return redirect()->back();
    }
}
