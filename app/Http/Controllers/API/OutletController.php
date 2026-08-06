<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    /**
     * Retrieve all active outlets for the current business.
     * Note: Intentionally no authorization check to allow internal UI components (like filters) to fetch outlets freely.
     */
    public function index(Request $request)
    {
        $outlets = Outlet::currentBusiness()
            ->active()
            ->select('id', 'name', 'is_stock_frozen')
            ->orderBy('name')
            ->get();

        return response()->json($outlets);
    }
}
