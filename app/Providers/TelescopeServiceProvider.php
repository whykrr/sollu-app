<?php

namespace App\Providers;

use App\Models\CockpitUser;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(static function (IncomingEntry $entry): bool {
            if ($entry->type === 'request' && isset($entry->content['uri']) && str_starts_with(ltrim((string) $entry->content['uri'], '/'), '_boost')) {
                return false;
            }

            if (! app()->isProduction()) {
                return true;
            }

            return $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->isSlowQuery()
                || $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
            'token',
            'secret',
            'card_number',
            'cvv',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Configure the Telescope authorization services.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(function ($request) {
            $user = $request->user('cockpit');

            if (! $user instanceof CockpitUser || $user->status !== 'active') {
                return false;
            }

            return Gate::forUser($user)->check('viewTelescope', [$user]);
        });
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (CockpitUser $user): bool {
            if ($user->status !== 'active') {
                return false;
            }

            $allowedEmails = array_filter(array_map('trim', explode(',', (string) env('TELESCOPE_ALLOWED_EMAILS', ''))));

            if (! empty($allowedEmails)) {
                return in_array($user->email, $allowedEmails, true);
            }

            return true;
        });
    }
}
