<?php

namespace App\Providers;

use App\Models\CockpitUser;
use App\Support\Pulse\MultiGuardPulseUsers;
use Illuminate\Support\Facades\Auth;
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

        Pulse::user(fn ($user) => [
            'name' => $user instanceof CockpitUser ? "{$user->name} (Cockpit)" : $user->name,
            'extra' => $user->email,
            'avatar' => method_exists($user, 'getPhotoUrlAttribute') ? $user->photo_url : null,
        ]);
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
