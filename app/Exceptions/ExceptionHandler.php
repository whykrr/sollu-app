<?php

namespace App\Exceptions;

use App\Constants\AuthorizationMessage;
use App\Constants\ErrorMessage;
use App\Constants\FlashDataVariable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionHandler
{
    /**
     * Configure application exception handling.
     */
    public function __invoke(Exceptions $exceptions): void
    {
        $this->configureJsonRendering($exceptions);
        $this->configureReporting($exceptions);
        $this->registerRenderers($exceptions);
    }

    /**
     * Determine when exceptions should render as JSON.
     */
    protected function configureJsonRendering(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $this->shouldRenderJson($request));
    }

    /**
     * Configure exception reporting and logging.
     */
    protected function configureReporting(Exceptions $exceptions): void
    {
        $exceptions->report(function (Throwable $e) {
            Log::error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    /**
     * Register renderable exception callbacks.
     */
    protected function registerRenderers(Exceptions $exceptions): void
    {
        // CSRF Token Mismatch / Expired Form
        $exceptions->render(function (TokenMismatchException|HttpException $e, Request $request) {
            if ($e instanceof TokenMismatchException || ($e instanceof HttpException && $e->getStatusCode() === 419)) {
                $request->session()->regenerateToken();

                if ($this->shouldRenderJson($request) || $request->header('X-Inertia')) {
                    $cookie = cookie(
                        'XSRF-TOKEN',
                        $request->session()->token(),
                        (int) config('session.lifetime', 120),
                        '/',
                        config('session.domain'),
                        config('session.secure'),
                        false,
                        false,
                        config('session.same_site', 'lax')
                    );

                    return response()->json([
                        'message' => 'Sesi formulir telah diperbarui.',
                        'csrf_token' => $request->session()->token(),
                        'authenticated' => $request->user() !== null,
                    ], 419)->withCookie($cookie);
                }

                return redirect()->back()->withInput($request->input())->with(FlashDataVariable::FAILED->value, 'Sesi formulir telah kedaluwarsa. Silakan coba kirim kembali.');
            }
        });

        // Authorization & Access Denied
        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e, Request $request) {
            $message = $e->getMessage();
            if (empty($message) || $message === 'This action is unauthorized.') {
                $message = AuthorizationMessage::CANT_ACCESS_PAGE;
            }

            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
            }

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $message);
        });

        // Database Error
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => ErrorMessage::DATABASE_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->back()->with(FlashDataVariable::FAILED->value, ErrorMessage::DATABASE_ERROR);
        });

        // Model / Data Not Found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => ErrorMessage::DATA_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back()->with(FlashDataVariable::FAILED->value, ErrorMessage::DATA_NOT_FOUND);
        });

        // Route / Page Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $isModelNotFound = $e->getPrevious() instanceof ModelNotFoundException;
            $message = $isModelNotFound ? ErrorMessage::DATA_NOT_FOUND : ErrorMessage::PAGE_NOT_FOUND;

            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $message);
        });

        // Throttle / Rate Limiting
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => ErrorMessage::TOO_MANY_REQUESTS], Response::HTTP_TOO_MANY_REQUESTS);
            }

            if ($request->is('login') || $request->is('register') || $request->is('forgot') || $request->is('reset-password')) {
                throw ValidationException::withMessages([
                    'email' => ErrorMessage::TOO_MANY_REQUESTS,
                ]);
            }

            return redirect()->back()->with(FlashDataVariable::FAILED->value, ErrorMessage::TOO_MANY_REQUESTS);
        });

        // HTTP Client / Business Errors (400 Bad Request, 422 Unprocessable, etc.)
        $exceptions->render(function (HttpException $e, Request $request) {
            $statusCode = $e->getStatusCode();
            if (in_array($statusCode, [419, 403, 404], true)) {
                return null;
            }

            if ($this->shouldRenderJson($request)) {
                return response()->json(['message' => $e->getMessage()], $statusCode);
            }

            if (in_array($statusCode, [400, 422], true)) {
                return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
            }
        });
    }

    /**
     * Check whether the incoming request expects a JSON response.
     */
    protected function shouldRenderJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*') || $request->getHost() === config('domain.api');
    }
}
