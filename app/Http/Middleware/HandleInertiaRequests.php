<?php

namespace App\Http\Middleware;

use App\Helpers\SelectedOutlet;
use App\Helpers\SummaryUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
    public function rootView(Request $request): string
    {
        return $request->routeIs('cockpit.*')
            ? 'cockpit'
            : 'app';
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
        if ($request->routeIs('cockpit.*')) {
            return array_merge(parent::share($request), [
                'app' => [
                    'name'        => config('app.name'),
                    'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
                    'flash'       => [
                        'success' => $request->session()->get('success'),
                        'failed'  => $request->session()->get('failed'),
                        'info'    => $request->session()->get('info'),
                    ],
                ],
                'auth' => fn () => $request->user()
                    ? array_merge(
                        $request->user()->only(['id', 'name', 'email', 'email_verified_at']),
                    ) : null,
            ]);
        } else {
            return array_merge(parent::share($request), [
                'app' => [
                    'name'        => config('app.name'),
                    'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
                    'flash'       => [
                        'success' => $request->session()->get('success'),
                        'failed'  => $request->session()->get('failed'),
                        'info'    => $request->session()->get('info'),
                    ],
                ],

                'auth' => fn () => $request->user()
                    ? array_merge(
                        $request->user()->only(['id', 'name', 'email', 'email_verified_at']),
                        (array) SummaryUser::make()->cached(),
                        ['selected_outlet' => '']
                    ) : null,

                'notifications' => Inertia::lazy(fn () => $request->user()->notifications()->get()),
                'businessInfo'  => Inertia::lazy(function () use ($request) {
                    $business = $request->user()->business;

                    return [
                        // 'subscription' => $business->subscriptions()
                        //     ->where('is_active', '=', true)
                        //     ->with('plan')
                        //     ->latest()
                        //     ->first(),
                        'outlet_count' => $business->outlets()
                            ->where('is_active', '=', true)->count(),
                        'businessType' => $business->type()->first()->name,
                    ];
                }),

                'selectedOutlet' => fn () => $request->user() ? SelectedOutlet::make()->cached() : null,
            ]);
        }

    }
}
