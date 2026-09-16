<?php

namespace App\Providers;

use Cache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['auth']->provider('eloquent_redis', function ($app, array $config) {
            return new \App\Auth\EloquentRedisUserProvider($app['hash'], $config['model']);
        });

        if ($this->app->environment('local', 'development')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        // telescope config
        if (config('telescope.enabled', env('TELESCOPE_ENABLED', true)) && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('login', function (HttpRequest $request) {
            return Limit::perMinute(5, 10)->by($request->input('email') ?: $request->ip());
        });

        Cache::macro('forgetPattern', function (string $pattern) {
            $keys = Redis::connection('cache')->keys($pattern);

            foreach ($keys as $key) {
                Cache::delete($key);
            }
        });

        if (! $this->app->environment('production')) {
            DB::listen(function ($query) {
                // Log query yang dijalankan pada mode development
                Log::channel('query_log')->info("Query executed: {$query->sql}", [
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            });
        }

        \App\Models\User::observe(\App\Observers\UserCacheObserver::class);
        \App\Models\Business::observe(\App\Observers\UserCacheObserver::class);
        \App\Models\Outlet::observe(\App\Observers\UserCacheObserver::class);
        \App\Models\Subscription::observe(\App\Observers\UserCacheObserver::class);
        \App\Models\SubscriptionPlan::observe(\App\Observers\UserCacheObserver::class);
        \App\Models\Feature::observe(\App\Observers\UserCacheObserver::class);
        \Spatie\Permission\Models\Role::observe(\App\Observers\UserCacheObserver::class);
        \Spatie\Permission\Models\Permission::observe(\App\Observers\UserCacheObserver::class);

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Authenticated::class, function ($event) {
            if (isset($event->user->business_id)) {
                setPermissionsTeamId($event->user->business_id);
            }
        });
    }
}
