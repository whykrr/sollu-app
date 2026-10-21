<?php

namespace App\Http\Middleware;

use App\Helpers\SelectedOutlet;
use App\Helpers\SummaryUser;
use App\Models\SystemSetting;
use App\Support\Breadcrumbs\BreadcrumbManager;
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
                'breadcrumbs' => BreadcrumbManager::forApp($request),
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

            'auth' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }

                $selectedOutlet = SelectedOutlet::make($request->user())->cached();

                return array_merge(
                    $request->user()->only(['id', 'name', 'email', 'email_verified_at', 'photo', 'photo_url', 'is_root_user']),
                    (array) SummaryUser::make($request->user())->cached(),
                    ['selected_outlet' => $selectedOutlet]
                );
            },

            'selectedOutlet' => fn () => $request->user() ? SelectedOutlet::make($request->user())->cached() : null,

            'subscription_lock' => function () use ($request) {
                $user = $request->user();
                if (! $user || ! $user->business_id || ! $user->business) {
                    return [
                        'is_locked' => false,
                        'lock_reason' => null,
                        'invoice_url' => null,
                        'plans_url' => null,
                    ];
                }

                $business = $user->business;
                $activeSub = $business->getActiveSubscriptionWithPlan();
                if ($activeSub) {
                    return [
                        'is_locked' => false,
                        'lock_reason' => null,
                        'invoice_url' => null,
                        'plans_url' => null,
                    ];
                }

                $pendingInvoice = $business->invoices()->where('status', 'open')->latest()->first();
                $latestSub = $business->subscriptions()->latest()->first();

                $lockReason = 'subscription_expired';
                if ($pendingInvoice) {
                    $lockReason = 'payment_pending';
                } elseif ($latestSub && $latestSub->is_trial) {
                    $lockReason = 'trial_expired';
                }

                return [
                    'is_locked' => true,
                    'lock_reason' => $lockReason,
                    'invoice_url' => $pendingInvoice ? route('settings.billing.invoices.show', $pendingInvoice->invoice_number) : null,
                    'plans_url' => route('settings.billing.plans'),
                ];
            },
        ]);
    }
}
