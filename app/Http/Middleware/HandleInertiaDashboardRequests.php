<?php

namespace App\Http\Middleware;

use App\Helpers\SelectedOutlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
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

        // $all_outlets = $request->user()
        //     ? Cache::remember(
        //         "info:merchant:{$request->user()->merchant->id}:outlets",
        //         60 * 60,
        //         fn () => $request->user()->merchant->outlets->map(fn ($outlet) => $outlet->only(['id', 'name']))
        //     )
        //     : null;

        return array_merge(parent::share($request), [
            'app' => [
                'name'        => config('app.name'),
                'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
                'flash'       => [
                    'success' => $request->session()->get('success'),
                    'failed'  => $request->session()->get('failed'),
                ],
            ],

            'auth' => fn () => $request->user()
                ? array_merge(
                    $request->user()->only(['id', 'name', 'email', 'email_verified_at']),
                    $this->getCachedUserSummary($request->user()),
                    ['selected_outlet' => '']
                ) : null,

            'notifications' => Inertia::lazy(fn () => $request->user()->notifications()->get()),

            'outlet' => fn () => $request->user() ? SelectedOutlet::make()->cached() : null,
        ]);
    }

    private function getCachedUserSummary(User $user)
    {
        return Cache::remember(
            "auth:user:{$user->id}:summary",
            60 * 60,
            function () use ($user) {
                $outlets = $user->outlets->map(fn ($outlet) => $outlet->only('id', 'name'));
                if (count($outlets) === 0) {
                    $outlets = $user->merchant->outlets->map(fn ($outlet) => $outlet->only('id', 'name'));
                }

                return [
                    'role'     => $user->roles()->pluck('label', 'name')->toArray(),
                    'merchant' => $user->merchant->with(['outlets', 'type'])->first(),
                    'outlets'  => $outlets,
                ];
            }
        );
    }
}
