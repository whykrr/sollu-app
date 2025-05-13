<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserChangePasswordController extends Controller
{
    public function index()
    {
        return inertia('User/ChangePassword', [
            'user' => Auth::user(),
        ]);
    }

    public function store(User $user, Request $request)
    {
        // check old password first
        if (!Auth::attempt([
            'email' => Auth::user()->email,
            'password' => $request->old_password,
        ])) {
            return back()->withErrors([
                'old_password' => 'Old password is incorrect!',
            ]);
        }

        $user->update($request->validate([
            'password' => ['required', 'confirmed'],
        ]));

        return redirect()->route('admin.dashboard')->with('success', 'Password has been changed!');
    }
}
