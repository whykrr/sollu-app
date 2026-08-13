<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ConfigController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Config/Index');
    }
}
