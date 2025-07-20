<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        $menuActiveContent = $request->routeIs('admin.contents.*')
            ? 'admin.contents.' . ($request->route()->parameter('content_type')->parent_id ?? $request->route()->parameter('content_type')->id)
            : null;

        return array_merge(parent::share($request), [
            // Synchronously...
            'appName'     => config('app.name'),
            'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
            'menuActive'  => $menuActiveContent ?? $request->route()->getName(),
            'flash'       => [
                'success' => $request->session()->get('success'),
                'failed'  => $request->session()->get('failed'),
            ],
            'request' => $request->getPayload(),

            // Lazily...
            'auth' => fn () => $request->user() ? array_merge(
                $request->user()->only(['id', 'name', 'email', 'email_verified_at']),
                [
                    'role'     => $request->user()->roles->pluck('label'),
                    'merchant' => optional($request->user()->merchant)->name,
                ]
            ) : null,
        ]);
    }
}
