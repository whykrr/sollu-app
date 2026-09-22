<?php

namespace App\Providers;

use App\Models\CockpitUser;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Configure the Horizon authorization services.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            $user = $request->user('cockpit');

            if (! $user instanceof CockpitUser || $user->status !== 'active') {
                return false;
            }

            return Gate::forUser($user)->check('viewHorizon', [$user]);
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (CockpitUser $user): bool {
            if ($user->status !== 'active') {
                return false;
            }

            $allowedEmails = array_filter(array_map('trim', explode(',', (string) env('HORIZON_ALLOWED_EMAILS', ''))));

            if (! empty($allowedEmails)) {
                return in_array($user->email, $allowedEmails, true);
            }

            return true;
        });
    }
}
