<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordHasBeenReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function index(Request $request)
    {
        return inertia('Dashboard/User/ForgotPassword');
    }

    public function sendEmailReset(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['success' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function reset(Request $request)
    {
        return inertia('Dashboard/User/ResetPassword', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    public function doReset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                $user->notify(new PasswordHasBeenReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('dashboard.login')->with('success', __($status))
            : back()->withErrors(['password' => __($status)]);
    }
}
