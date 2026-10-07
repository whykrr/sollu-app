<?php

namespace App\Providers;

use App\Models\CockpitUser;
use App\Models\OutletDevice;
use App\Models\User;
use App\Support\Pulse\MultiGuardPulseUsers;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\Facades\Pulse;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ResolvesUsers::class, MultiGuardPulseUsers::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->gate();

        Event::listen(Authenticated::class, function (Authenticated $event): void {
            Pulse::rememberUser($event->user);
        });

        Pulse::user(function ($user) {
            if ($user instanceof OutletDevice) {
                $outletName = $user->outlet?->name;

                return [
                    'name' => "{$user->device_name} (Perangkat)",
                    'extra' => $outletName ? "Outlet: {$outletName}" : 'Perangkat POS',
                    'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->device_name).'&background=6366f1&color=fff',
                ];
            }

            if ($user instanceof CockpitUser) {
                return [
                    'name' => "{$user->name} (Cockpit)",
                    'extra' => $user->email,
                    'avatar' => sprintf('https://gravatar.com/avatar/%s?d=mp', hash('sha256', trim(strtolower($user->email)))),
                ];
            }

            if ($user instanceof User) {
                $outletName = $user->is_root_user
                    ? 'Semua Outlet'
                    : ($user->outlets->pluck('name')->implode(', ') ?: '-');

                return [
                    'name' => $user->name,
                    'extra' => "Outlet: {$outletName}".($user->email ? " • {$user->email}" : ''),
                    'avatar' => method_exists($user, 'getPhotoUrlAttribute') ? $user->photo_url : null,
                ];
            }

            return [
                'name' => $user->name ?? 'User',
                'extra' => $user->email ?? null,
                'avatar' => null,
            ];
        });
    }

    /**
     * Register the Pulse gate.
     */
    protected function gate(): void
    {
        Gate::define('viewPulse', function ($user = null): bool {
            $cockpitUser = Auth::guard('cockpit')->user();

            if (! $cockpitUser instanceof CockpitUser || $cockpitUser->status !== 'active') {
                return false;
            }

            $allowedEmails = array_filter(array_map('trim', explode(',', (string) env('PULSE_ALLOWED_EMAILS', ''))));

            if (! empty($allowedEmails)) {
                return in_array($cockpitUser->email, $allowedEmails, true);
            }

            return true;
        });
    }
}
