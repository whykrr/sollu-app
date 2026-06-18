<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Invoice/Index');
    }
}
