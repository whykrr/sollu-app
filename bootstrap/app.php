<?php

use App\Exceptions\ExceptionHandler;
use App\Http\Middleware\CheckPlanFeature;
use App\Http\Middleware\ConfigureDomainSession;
use App\Http\Middleware\EnsureStockNotFrozen;
use App\Http\Middleware\HandleAppInertiaRequests;
use App\Http\Middleware\HandleCockpitInertiaRequests;
use App\Http\Middleware\VerifyPosDevice;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            $appDomain = config('domain.app', 'app.sollu.test');
            $cockpitDomain = config('domain.cockpit', 'cockpit.sollu.test');
            $apiDomain = config('domain.api', 'api.sollu.test');

            Route::middleware(['web', HandleAppInertiaRequests::class])
                ->domain($appDomain)
                ->group(base_path('routes/app.php'));

            Route::middleware(['web', HandleCockpitInertiaRequests::class])
                ->domain($cockpitDomain)
                ->group(base_path('routes/cockpit.php'));

            Route::middleware('api')
                ->domain($apiDomain)
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            ConfigureDomainSession::class,
        ]);

        $middleware->web(append: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if (str_starts_with($request->getHost(), 'cockpit.') || $request->getHost() === config('domain.cockpit')) {
                return route('cockpit.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if (str_starts_with($request->getHost(), 'cockpit.') || $request->getHost() === config('domain.cockpit')) {
                return route('cockpit.dashboard');
            }

            return route('overview');
        });

        $middleware->alias([
            'stock.not.frozen' => EnsureStockNotFrozen::class,
            'pos.device' => VerifyPosDevice::class,
            'plan.feature' => CheckPlanFeature::class,
        ]);
    })
    ->withExceptions(new ExceptionHandler)
    ->create();
