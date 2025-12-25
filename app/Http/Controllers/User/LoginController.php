<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        return inertia('User/Login');
    }
    public function store(Request $request)
    {
        if (! Auth::attempt(
            $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required'],
            ]),
            $request->filled('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'Autentikasi gagal, silakan periksa kembali email dan kata sandi Anda!',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('overview'));
    }

    public function destroy(Request $request)
    {
        $id = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cache::forgetPattern("auth:user:{$id}:*");

        return redirect()->route('login');
    }
}
