<?php

namespace App\Http\Middleware;

use App\Helpers\ContentDisplay;
use App\Models\ContentType;
use App\Models\Setting;
use Cache;
use Inertia\Middleware;
use Illuminate\Http\Request;

class HandleInertiaWebRequests extends Middleware
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
        $settings = (array) Cache::get('setting.website', function () {
            $website = Setting::where('key', '=', 'website')->first();
            Cache::forever('setting.website', $website->value);
            return $website->value;
        });
        $socialMedia = (array) Cache::get('setting.social_media', function () {
            $sm = Setting::where('key', '=', 'social_media')->first();
            Cache::forever('setting.social_media', $sm->value);
            return $sm->value;
        });

        $others = Cache::get('website.others', function () {
            $content = ContentDisplay::single(ContentType::where('slug', '=', 'lain-lain')
                ->with(['content.field_values.content_field'])
                ->first());
            Cache::forever('website.others', $content);

            return $content;
        });

        $links = Cache::get('website.links', function () {
            $content = ContentDisplay::singleListed(ContentType::where('slug', '=', 'tautan')
                ->with(['content.field_values.content_field'])
                ->first());
            Cache::forever('website.links', $content);

            return $content;
        });

        return array_merge(parent::share($request), [
            // Synchronously...
            'appName' => config('app.name'),
            'settings' => $settings,
            'socialMedia' => $socialMedia,
            'links' => $links,
            'others' => $others,
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'breadcrumbs' => generateBreadcrumbs($request->route()->getName()),
        ]);
    }
}
