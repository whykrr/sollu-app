<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (app()->environment('production')) {
            if (!session()->has('unique_visitor' . $request->url())) {
                session(['unique_visitor' . $request->url() => true]);

                Visitor::create([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'url' => $request->url(),
                    'referrer' => $request->header('Referer'),
                    'session_id' => session()->getId(),
                    'created_month' => Carbon::now()->format('Y-m'),
                ]);
            }
        }

        return $next($request);
    }
}
