<?php

namespace App\Http\Middleware;

use App\Models\User;
use Cache;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaDashboardRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'dashboard';

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

        $all_outlets = $request->user()
            ? Cache::remember(
                "info:merchant:{$request->user()->merchant->id}:outlets",
                60 * 60,
                fn () => $request->user()->merchant->outlets->map(fn ($outlet) => $outlet->only(['id', 'name']))
            )
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
            'request'          => $request->getPayload(),
            'merchant_outlets' => $all_outlets,

            // Lazily...
            'auth' => fn () => $request->user() ? $this->getCachedUserSummary($request->user()) : null,
        ]);
    }

    private function getCachedUserSummary(User $user)
    {
        return Cache::remember(
            "auth:user:{$user->id}:summary",
            60 * 60,
            function () use ($user) {
                return array_merge(
                    $user->only(['id', 'name', 'email', 'email_verified_at']),
                    [
                        'role'     => $user->roles()->pluck('label', 'name')->toArray(),
                        'merchant' => optional($user->merchant)->name,
                        'outlets'  => $user->outlets->map(fn ($outlet) => $outlet->only('id', 'name')),
                    ]
                );
            }
        );
    }
}
