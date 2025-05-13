<?php

use App\Http\Middleware\TrackVisitor;
use Illuminate\Foundation\Application;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleInertiaWebRequests;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('admin')
                ->prefix('administrator')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'track-visitor' => TrackVisitor::class
        ]);
        $middleware->web(append: [
            HandleInertiaWebRequests::class
        ]);
        $middleware->group('admin', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            HandleInertiaRequests::class
        ]);

        //app
        $middleware->redirectGuestsTo(fn($request) => route('admin.login'));
        $middleware->redirectUsersTo(fn($request) => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
