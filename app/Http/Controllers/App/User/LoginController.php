<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\User;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

    public function index(): Response
    {
        return inertia('User/Login');
    }

    public function store(Request $request): Response|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'business_id' => ['nullable', 'uuid'],
        ]);

        $users = User::query()
            ->with(['business', 'roles:id,name,label'])
            ->where('email', $credentials['email'])
            ->get();

        if ($users->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'Autentikasi gagal, silakan periksa kembali email dan kata sandi Anda!',
            ]);
        }

        // Filter users whose password matches
        $matchedUsers = $users->filter(function (User $user) use ($credentials): bool {
            return ! empty($user->password) && Hash::check($credentials['password'], $user->password);
        });

        if ($matchedUsers->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'Autentikasi gagal, silakan periksa kembali email dan kata sandi Anda!',
            ]);
        }

        // If specific business_id is requested (selected from business selection screen)
        if (! empty($credentials['business_id'])) {
            $selectedUser = $matchedUsers->firstWhere('business_id', $credentials['business_id']);

            if (! $selectedUser) {
                throw ValidationException::withMessages([
                    'email' => 'Bisnis yang dipilih tidak sesuai dengan kredensial Anda.',
                ]);
            }

            return $this->performLogin($request, $selectedUser);
        }

        // If exactly 1 matching account found (normal case)
        if ($matchedUsers->count() === 1) {
            return $this->performLogin($request, $matchedUsers->first());
        }

        // If multiple matching accounts found, render tenant selector
        return inertia('User/SelectBusiness', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'remember' => $request->boolean('remember'),
            'businesses' => $matchedUsers->map(function (User $user): array {
                return [
                    'id' => $user->business_id,
                    'name' => $user->business?->name ?? 'Bisnis',
                    'role' => $user->is_root_user ? 'Pemilik Usaha' : ($user->roles->first()?->label ?? 'Staf'),
                ];
            })->values()->all(),
        ]);
    }

    protected function performLogin(Request $request, User $user): RedirectResponse
    {
        Auth::guard('business')->login($user, $request->boolean('remember'));

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

    public function destroy(Request $request): RedirectResponse
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
