<?php

namespace App\Http\Controllers\App\User;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

    public function index()
    {
        return inertia('User/Login');
    }

    public function store(Request $request)
    {
        if (! Auth::guard('business')->attempt(
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]),
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'Autentikasi gagal, silakan periksa kembali email dan kata sandi Anda!',
            ]);
        }

        $user = Auth::user();
        $user->last_login_at = now();
        $user->save();

        $this->auditLogger->log(
            module: AuditModuleEnum::AUTH->value,
            action: 'auth.login',
            description: "Pengguna {$user->name} berhasil masuk ke sistem",
            subject: $user,
            causer: $user,
            businessId: $user->business_id
        );

        $request->session()->regenerate();

        return redirect()->intended(route('overview'));
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        $id = Auth::id();

        if ($user) {
            $this->auditLogger->log(
                module: AuditModuleEnum::AUTH->value,
                action: 'auth.logout',
                description: "Pengguna {$user->name} keluar dari sistem",
                subject: $user,
                causer: $user,
                businessId: $user->business_id
            );
        }

        Auth::guard('business')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cache::forgetPattern("auth:user:{$id}:*");

        return redirect()->route('login');
    }
}
