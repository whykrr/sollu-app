<?php

namespace App\Http\Middleware;

use App\Helpers\SelectedOutlet;
use App\Helpers\SummaryUser;
use App\Models\SystemSetting;
use App\Support\Enums\FrontendEnumProvider;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleAppInertiaRequests extends Middleware
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
        return 'app';
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
                'home_route' => 'overview',
                'breadcrumbs' => \App\Support\Breadcrumbs\BreadcrumbManager::forApp($request),
                'help_center_url' => fn () => SystemSetting::get('help_center_url', '#'),
                'whatsapp_support_number' => fn () => SystemSetting::get('whatsapp_support_number', ''),
                'flash' => [
                    'success' => $request->session()->get('success'),
                    'failed' => $request->session()->get('failed'),
                    'error' => $request->session()->get('error'),
                    'info' => $request->session()->get('info'),
                    'otp_data' => $request->session()->get('otp_data'),
                    'feature_locked' => $request->session()->get('feature_locked'),
                ],
            ],

            'enums' => fn () => FrontendEnumProvider::all(),

            'auth' => fn () => $request->user()
                ? array_merge(
                    $request->user()->only(['id', 'name', 'email', 'email_verified_at', 'photo', 'photo_url']),
                    (array) SummaryUser::make()->cached(),
                    ['selected_outlet' => SelectedOutlet::make($request->user())->cached()]
                ) : null,

            'selectedOutlet' => fn () => $request->user() ? SelectedOutlet::make($request->user())->cached() : null,
        ]);
    }
}
