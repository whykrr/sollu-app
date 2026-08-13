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
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (HttpRequest $request) {
            return Limit::perMinute(5, 10)->by($request->input('email') ?: $request->ip());
        });

        Cache::macro('forgetPattern', function (string $pattern) {
            $keys = Redis::connection('cache')->keys($pattern);

            foreach ($keys as $key) {
                Cache::delete($key);
            }
        });

        DB::listen(function ($query) {
            // Log query yang dijalankan
            Log::channel('query_log')->info("Query executed: {$query->sql}", [
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });
    }
}
