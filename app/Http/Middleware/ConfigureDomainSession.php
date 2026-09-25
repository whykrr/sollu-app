<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class ConfigureDomainSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $cockpitDomain = config('domain.cockpit', 'cockpit.sollu.test');

        if (str_starts_with($host, 'cockpit.') || $host === $cockpitDomain) {
            $cookieName = env('SESSION_COOKIE_COCKPIT', 'sollu_cockpit_session');
        } else {
            $cookieName = env('SESSION_COOKIE_APP', 'sollu_app_session');
        }

        if (config('session.cookie') !== $cookieName) {
            config(['session.cookie' => $cookieName]);
        }

        if (app()->resolved('session')) {
            /** @var SessionManager $manager */
            $manager = app('session');
            try {
                $driver = $manager->driver();
                $driver->setName($cookieName);
            } catch (\Throwable) {
                // Ignore if driver cannot be resolved yet
            }
        }

        $response = $next($request);

        // Proactively expire legacy wildcard domain cookies (.sollu.test)
        // that collide with new host-only cookies in browsers
        $hostParts = explode('.', $host);
        if (count($hostParts) >= 3) {
            $rootDomain = '.'.implode('.', array_slice($hostParts, -2));
            if ($request->cookies->has('sollu_teknologi_indonesia_session')) {
                $response->headers->setCookie(new Cookie('sollu_teknologi_indonesia_session', '', 1, '/', $rootDomain));
            }
            if ($request->cookies->has('laravel_session')) {
                $response->headers->setCookie(new Cookie('laravel_session', '', 1, '/', $rootDomain));
            }
            if ($request->cookies->has('XSRF-TOKEN')) {
                $response->headers->setCookie(new Cookie('XSRF-TOKEN', '', 1, '/', $rootDomain, false, false));
            }
        }

        return $response;
    }
}
