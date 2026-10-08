<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachApiDeprecationHeader
{
    /**
     * Handle an incoming request and attach standard RFC 8594 deprecation headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $warning = null): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', 'Sat, 01 Jan 2028 00:00:00 GMT');
        $response->headers->set('Link', '<http://api.sollu.test/docs>; rel="deprecation"');

        $message = $warning ?? 'Endpoint unversioned/legacy ini telah usang. Silakan bermigrasi ke endpoint canonical /v1/... pada http://api.sollu.test';
        $response->headers->set('X-API-Deprecation-Warning', $message);

        return $response;
    }
}
