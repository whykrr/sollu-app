<?php

use App\Http\Middleware\HandleInertiaDashboardRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/health',
        then: function () {
            // This is where you can add any additional middleware to the web routes.
            // For example, you can add authentication or authorization middleware here.
            Route::domain(config('domain.dashboard'))
                ->middleware(['web', 'dashboard'])
                ->name('dashboard.')
                ->group(base_path('routes/dashboard.php'));

            Route::domain(config('domain.api'))
                ->middleware(['api'])
                ->name('api.')
                ->group(base_path('routes/api.php'));
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
            // HandleInertiaRequests::class,
        ]);

        $middleware->group('dashboard', [
            HandleInertiaDashboardRequests::class,
        ]);

        //app
        $middleware->redirectGuestsTo(function ($request) {
            $host = $request->getHost();
            if (str_starts_with($host, 'dashboard.')) {
                return route('dashboard.login');
            }

            return route('home');
        });
        $middleware->redirectUsersTo(fn ($request) => route('dashboard.overview'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            Log::error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        });

        // Error Authorization
        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
            }

            return redirect()->back()->with('failed', $e->getMessage());
        });

        // Error DB
        $exceptions->renderable(function (QueryException $e, Request $request) {
            $message = 'Terjadi kesalahan database. coba lagi nanti.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->back()->with('failed', $message);
        });


        // Error Data Not Found
        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            $message = 'Data tidak ditemukan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back()->with('failed', $message);
        });

        // Error Page Not Found
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            $message = 'Halaman tidak ditemukan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back()->with('failed', $message);
        });

        // Error Throttle
        $exceptions->renderable(function (ThrottleRequestsException $e, Request $request) {
            $message = 'Terlalu banyak permintaan. Coba lagi nanti.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // if page for guest
            if ($request->is('dashboard.login') || $request->is('dashboard.register') || $request->is('forgot')) {
                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            return redirect()->back()->with('failed', $message);
        });


    })->create();
