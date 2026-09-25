<?php

namespace App\Providers;

use App\Auth\EloquentRedisUserProvider;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Contracts\Inventory\InventoryDeductionServiceInterface;
use App\Models\Business;
use App\Models\Feature;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Observers\UserCacheObserver;
use App\Services\App\Audit\ActivityLogService;
use App\Services\App\Inventory\InventoryDeductionService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Cache;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['auth']->provider('eloquent_redis', function ($app, array $config) {
            return new EloquentRedisUserProvider($app['hash'], $config['model']);
        });

        if ($this->app->environment('local', 'development')) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        // telescope config (strictly local/development only)
        if ($this->app->environment('local', 'development')
            && config('telescope.enabled', env('TELESCOPE_ENABLED', true))
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(
            InventoryDeductionServiceInterface::class,
            InventoryDeductionService::class
        );

        $this->app->singleton(
            ActivityLoggerInterface::class,
            ActivityLogService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (HttpRequest $request) {
            return Limit::perMinute(5, 10)->by($request->input('email') ?: $request->ip());
        });

        Cache::macro('forgetPattern', function (string $pattern) {
            try {
                if (config('cache.default') !== 'redis') {
                    return;
                }
                $keys = Redis::connection('cache')->keys($pattern);

                foreach ($keys as $key) {
                    Cache::delete($key);
                }
            } catch (\Throwable) {
                // Ignore if redis is unavailable or during test environment
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

        User::observe(UserCacheObserver::class);
        Business::observe(UserCacheObserver::class);
        Outlet::observe(UserCacheObserver::class);
        Subscription::observe(UserCacheObserver::class);
        SubscriptionPlan::observe(UserCacheObserver::class);
        Feature::observe(UserCacheObserver::class);
        Role::observe(UserCacheObserver::class);
        Permission::observe(UserCacheObserver::class);

        Event::listen(Authenticated::class, function ($event) {
            if (isset($event->user->business_id)) {
                setPermissionsTeamId($event->user->business_id);
            }
        });
    }
}
