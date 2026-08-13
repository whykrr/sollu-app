<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class UomController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Uom/Index');
    }
}
