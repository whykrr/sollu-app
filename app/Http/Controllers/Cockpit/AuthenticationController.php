<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthenticationController extends Controller
{
    public function index()
    {
        return Inertia::render('Cockpit/Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('cockpit')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('cockpit.dashboard');
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::guard('cockpit')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('cockpit.login');
    }
}
