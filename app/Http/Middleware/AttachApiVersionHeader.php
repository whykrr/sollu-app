<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachApiVersionHeader
{
    /**
     * Handle an incoming request and attach API version header.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        $response = $next($request);

        $response->headers->set('X-API-Version', $version);

        return $response;
    }
}
