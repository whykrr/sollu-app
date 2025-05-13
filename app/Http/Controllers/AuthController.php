<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function index()
    {
        return inertia('User/Login');
    }
    public function store(Request $request)
    {
        if (!Auth::attempt(
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ])
        )) {
            throw ValidationException::withMessages([
                'email' => 'Authentication failed, please check your credentials!'
            ]);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
