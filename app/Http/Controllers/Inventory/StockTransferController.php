<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;

class StockTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Inventory/Transfer/Index', []);

    }
}
