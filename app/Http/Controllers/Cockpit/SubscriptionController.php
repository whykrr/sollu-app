<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Subscription/Index');
    }

    public function show($id)
    {
        return Inertia::render('Cockpit/Subscription/Show', ['id' => $id]);
    }
}
