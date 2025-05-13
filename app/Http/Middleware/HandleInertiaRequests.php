<?php

namespace App\Http\Middleware;

use App\Models\ContentType;
use App\Models\Setting;
use Cache;
use Inertia\Middleware;
use Illuminate\Http\Request;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'admin';

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

        $contentType = Cache::get('content-sidebar', function () {
            $ct = ContentType::sidebar()->get()->toJson();
            Cache::forever('content-sidebar', $ct);
            return $ct;
        });

        $locale = Cache::get('system-locale', function () {
            $lang = Setting::find('system')->value['language'];
            Cache::forever('system-locale', $lang);
            return $lang;
        });

        $menuActiveContent = $request->routeIs('admin.contents.*')
            ? "admin.contents." . ($request->route()->parameter('content_type')->parent_id ?? $request->route()->parameter('content_type')->id)
            : null;

        return array_merge(parent::share($request), [
            // Synchronously...
            'appName' => config('app.name'),
            'locale' => $locale,
            'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
            'menuActive' => $menuActiveContent ?? $request->route()->getName(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'failed' => $request->session()->get('failed')
            ],
            'request' => $request->getPayload(),

            // Lazily...
            'auth' => fn() => $request->user()?->only('id', 'name', 'email', 'role'),
            'contentSidebar' => fn() => json_decode((string) $contentType)
        ]);
    }
}
