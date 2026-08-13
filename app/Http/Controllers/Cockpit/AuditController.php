<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Audit/Index');
    }
}
