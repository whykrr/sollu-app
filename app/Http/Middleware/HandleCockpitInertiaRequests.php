<?php

namespace App\Http\Middleware;

use App\Support\Breadcrumbs\BreadcrumbManager;
use App\Support\Enums\FrontendEnumProvider;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleCockpitInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    public function rootView(Request $request): string
    {
        return 'cockpit';
    }

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
        return array_merge(parent::share($request), [
            'app' => [
                'name' => config('app.name'),
                'home_route' => 'cockpit.dashboard',
                'breadcrumbs' => BreadcrumbManager::forCockpit($request),
                'flash' => [
                    'success' => $request->session()->get('success'),
                    'failed' => $request->session()->get('failed'),
                    'info' => $request->session()->get('info'),
                ],
            ],
            'enums' => fn () => FrontendEnumProvider::all(),
            'auth' => fn () => $request->user()
                ? array_merge(
                    $request->user()->only(['id', 'name', 'email', 'email_verified_at']),
                ) : null,
            'features' => [
                'has_telescope' => ! app()->isProduction() && (bool) config('telescope.enabled', true),
                'has_horizon' => true,
                'has_pulse' => true,
            ],
        ]);
    }
}
