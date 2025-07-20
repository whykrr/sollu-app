<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/health',
        then: function () {
            // This is where you can add any additional middleware to the web routes.
            // For example, you can add authentication or authorization middleware here.
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            HandleInertiaRequests::class,
        ]);

        //app
        $middleware->redirectGuestsTo(fn ($request) => route('login'));
        // $middleware->redirectUsersTo(fn($request) => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (ThrottleRequestsException $e, Request $request) {
            $message = 'Terlalu banyak permintaan. Coba lagi nanti.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 429);
            }

            // if page for guest
            if ($request->is('login') || $request->is('register') || $request->is('forgot')) {
                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            return redirect()->back()->with('failed', $message);
        });
    })->create();
