<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        return inertia('Dashboard/User/Login');
    }
    public function store(Request $request)
    {
        if (! Auth::attempt(
            $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required'],
            ]),
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'Autentikasi gagal, silakan periksa kembali email dan kata sandi Anda!',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.overview'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard.login');
    }
}
