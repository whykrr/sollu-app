<?php

namespace App\Services\Cockpit;

use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessStatusLog;
use App\Models\Outlet;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantService
{
    /**
     * Get paginated merchants list with optimized selective columns and eager loading.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedMerchants(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Business::query()
            ->select([
                'id',
                'name',
                'owner_name',
                'email',
                'phone',
                'business_type_id',
                'status',
                'trial_end_at',
                'created_at',
            ])
            ->with([
                'type:id,code,name',
                'subscriptions' => function ($q) {
                    $q->select([
                        'id',
                        'business_id',
                        'plan_id',
                        'status',
                        'billing_cycle',
                        'started_at',
                        'expired_at',
                    ])
                        ->where('status', SubscriptionStatus::Active->value)
                        ->latest('created_at')
                        ->with('plan:id,code,name,price_per_outlet');
                },
            ])
            ->withCount(['outlets', 'users'])
            ->withMax('users', 'last_login_at');

        // Search Keyword
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if (! empty($filters['status'])) {
            $statusVal = $filters['status'] instanceof BusinessStatus
                ? $filters['status']->value
                : (string) $filters['status'];

            $query->where('status', $statusVal);
        }

        // Business Type Filter
        if (! empty($filters['business_type_id'])) {
            $query->where('business_type_id', $filters['business_type_id']);
        }

        // Subscription Filter
        if (! empty($filters['subscription_status'])) {
            $subStatus = (string) $filters['subscription_status'];
            if ($subStatus === 'active') {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', SubscriptionStatus::Active->value)
                        ->where(function ($sq) {
                            $sq->whereNull('expired_at')
                                ->orWhere('expired_at', '>=', now());
                        });
                });
            } elseif ($subStatus === 'trial') {
                $query->where('trial_end_at', '>=', now())
                    ->whereDoesntHave('subscriptions', function ($q) {
                        $q->where('status', SubscriptionStatus::Active->value);
                    });
            } elseif ($subStatus === 'expired') {
                $query->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('trial_end_at', '<', now())
                            ->orWhereNull('trial_end_at');
                    })->whereDoesntHave('subscriptions', function ($sq) {
                        $sq->where('status', SubscriptionStatus::Active->value)
                            ->where(function ($ssq) {
                                $ssq->whereNull('expired_at')
                                    ->orWhere('expired_at', '>=', now());
                            });
                    });
                });
            }
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'name' => 'name',
            'owner_name' => 'owner_name',
            'status' => 'status',
            'created_at' => 'created_at',
            'users_max_last_login_at' => 'users_max_last_login_at',
        ];

        if (array_key_exists($sortField, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortField], $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get aggregate KPI metrics for Cockpit merchant management.
     *
     * @return array{
     *     total_merchants: int,
     *     active_merchants: int,
     *     suspended_merchants: int,
     *     active_subscribers: int,
     *     trial_merchants: int,
     *     total_outlets: int
     * }
     */
    public function getMetrics(): array
    {
        return Cache::remember('cockpit:merchants:metrics', 60, function () {
            $totalMerchants = Business::query()->count();
            $activeMerchants = Business::query()->where('status', BusinessStatus::Active->value)->count();
            $suspendedMerchants = Business::query()->where('status', BusinessStatus::Suspended->value)->count();

            $activeSubscribers = Subscription::query()
                ->where('status', SubscriptionStatus::Active->value)
                ->where(function ($q) {
                    $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>=', now());
                })
                ->distinct('business_id')
                ->count('business_id');

            $trialMerchants = Business::query()
                ->where('trial_end_at', '>=', now())
                ->count();

            $totalOutlets = Outlet::query()->whereNull('deleted_at')->count();

            return [
                'total_merchants' => $totalMerchants,
                'active_merchants' => $activeMerchants,
                'suspended_merchants' => $suspendedMerchants,
                'active_subscribers' => $activeSubscribers,
                'trial_merchants' => $trialMerchants,
                'total_outlets' => $totalOutlets,
            ];
        });
    }

    /**
     * Get detailed merchant information with real subscription, outlets, and users.
     *
     * @return array<string, mixed>
     */
    public function getMerchantDetail(string $id): array
    {
        $business = Business::query()
            ->with([
                'type:id,code,name',
                'outlets' => function ($q) {
                    $q->select([
                        'id',
                        'business_id',
                        'name',
                        'address',
                        'phone',
                        'email',
                        'is_active',
                        'is_main_outlet',
                        'is_stock_frozen',
                        'timezone',
                        'currency_code',
                        'created_at',
                    ])
                        ->orderBy('is_main_outlet', 'desc')
                        ->orderBy('name', 'asc');
                },
                'users' => function ($q) {
                    $q->select([
                        'id',
                        'business_id',
                        'name',
                        'email',
                        'phone',
                        'last_login_at',
                        'is_root_user',
                        'created_at',
                    ])
                        ->with('roles:id,name,label')
                        ->orderBy('is_root_user', 'desc')
                        ->orderBy('name', 'asc');
                },
                'subscriptions' => function ($q) {
                    $q->with([
                        'plan:id,code,name,price_per_outlet,yearly_discount_percent,features,business_id',
                        'plan.systemFeatures:id,code,name,module,group,group_label',
                    ])
                        ->latest('created_at');
                },
                'invoices' => function ($q) {
                    $q->select([
                        'id',
                        'business_id',
                        'invoice_number',
                        'status',
                        'total_amount',
                        'due_date',
                        'paid_at',
                        'created_at',
                    ])
                        ->latest('created_at')
                        ->limit(5);
                },
            ])
            ->withCount(['outlets', 'users', 'invoices'])
            ->findOrFail($id);

        // Resolve real active plan and subscription metadata
        $activeSubscription = $business->subscriptions
            ->where('status', SubscriptionStatus::Active)
            ->first();

        $activePlanInfo = null;
        if ($activeSubscription && $activeSubscription->plan) {
            $plan = $activeSubscription->plan;
            $activePlanInfo = [
                'type' => 'paid',
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_code' => $plan->code,
                'is_custom' => $plan->isCustomForMerchant(),
                'price_per_outlet' => (float) $plan->price_per_outlet,
                'yearly_discount_percent' => (int) $plan->yearly_discount_percent,
                'billing_cycle' => $activeSubscription->billing_cycle,
                'started_at' => $activeSubscription->started_at?->toISOString(),
                'expired_at' => $activeSubscription->expired_at?->toISOString(),
                'status' => $activeSubscription->status instanceof SubscriptionStatus
                    ? $activeSubscription->status->value
                    : (string) $activeSubscription->status,
                'status_label' => $activeSubscription->status instanceof SubscriptionStatus
                    ? $activeSubscription->status->label()
                    : 'Aktif',
                'status_color' => $activeSubscription->status instanceof SubscriptionStatus
                    ? $activeSubscription->status->color()
                    : 'badge-success',
                'system_features_count' => $plan->systemFeatures->count(),
                'system_features' => $plan->systemFeatures->map(fn ($feat) => [
                    'id' => $feat->id,
                    'code' => $feat->code,
                    'name' => $feat->name,
                    'module' => $feat->module,
                    'group_label' => $feat->group_label,
                ])->all(),
            ];
        } else {
            $isTrialActive = $business->trial_end_at && Carbon::parse($business->trial_end_at)->isFuture();
            if ($isTrialActive) {
                $trialDaysLeft = max(0, (int) ceil(now()->diffInDays(Carbon::parse($business->trial_end_at), false)));
                $activePlanInfo = [
                    'type' => 'trial',
                    'plan_id' => null,
                    'plan_name' => 'Masa Uji Coba (Trial)',
                    'plan_code' => 'trial',
                    'is_custom' => false,
                    'price_per_outlet' => 0.0,
                    'yearly_discount_percent' => 0,
                    'billing_cycle' => 'trial',
                    'started_at' => $business->created_at?->toISOString(),
                    'expired_at' => Carbon::parse($business->trial_end_at)->toISOString(),
                    'trial_days_left' => $trialDaysLeft,
                    'status' => 'trial',
                    'status_label' => 'Masa Trial',
                    'status_color' => 'badge-warning',
                    'system_features_count' => 0,
                    'system_features' => [],
                ];
            } else {
                $activePlanInfo = [
                    'type' => 'none',
                    'plan_id' => null,
                    'plan_name' => 'Tidak Ada Paket Aktif',
                    'plan_code' => 'none',
                    'is_custom' => false,
                    'price_per_outlet' => 0.0,
                    'yearly_discount_percent' => 0,
                    'billing_cycle' => '-',
                    'started_at' => null,
                    'expired_at' => $business->trial_end_at ? Carbon::parse($business->trial_end_at)->toISOString() : null,
                    'status' => 'expired',
                    'status_label' => 'Kedaluwarsa / Nonaktif',
                    'status_color' => 'badge-danger',
                    'system_features_count' => 0,
                    'system_features' => [],
                ];
            }
        }

        return [
            'id' => $business->id,
            'name' => $business->name,
            'owner_name' => $business->owner_name,
            'email' => $business->email,
            'phone' => $business->phone,
            'address' => $business->address,
            'logo_url' => $business->logo_url,
            'status' => $business->status instanceof BusinessStatus ? $business->status->value : (string) $business->status,
            'trial_end_at' => $business->trial_end_at?->toISOString(),
            'created_at' => $business->created_at?->toISOString(),
            'type' => $business->type ? [
                'id' => $business->type->id,
                'code' => $business->type->code,
                'name' => $business->type->name,
            ] : null,
            'outlets_count' => $business->outlets_count,
            'users_count' => $business->users_count,
            'invoices_count' => $business->invoices_count,
            'active_plan' => $activePlanInfo,
            'outlets' => $business->outlets->map(fn ($outlet) => [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'address' => $outlet->address,
                'phone' => $outlet->phone,
                'email' => $outlet->email,
                'is_active' => (bool) $outlet->is_active,
                'is_main_outlet' => (bool) $outlet->is_main_outlet,
                'is_stock_frozen' => (bool) $outlet->is_stock_frozen,
                'timezone' => $outlet->timezone,
                'currency_code' => $outlet->currency_code,
                'created_at' => $outlet->created_at?->toISOString(),
            ])->all(),
            'users' => $business->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_root_user' => (bool) $user->is_root_user,
                'last_login_at' => $user->last_login_at?->toISOString(),
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label ?? $role->name,
                ])->all(),
            ])->all(),
            'subscription_history' => $business->subscriptions->map(fn ($sub) => [
                'id' => $sub->id,
                'plan_name' => $sub->plan?->name ?? 'Paket Kustom',
                'status' => $sub->status instanceof SubscriptionStatus ? $sub->status->value : (string) $sub->status,
                'status_label' => $sub->status instanceof SubscriptionStatus ? $sub->status->label() : (string) $sub->status,
                'status_color' => $sub->status instanceof SubscriptionStatus ? $sub->status->color() : 'badge-gray',
                'billing_cycle' => $sub->billing_cycle,
                'started_at' => $sub->started_at?->toISOString(),
                'expired_at' => $sub->expired_at?->toISOString(),
            ])->all(),
        ];
    }

    /**
     * Toggle merchant active or suspended status with transaction and audit log.
     */
    public function toggleStatus(string $id, string $newStatus, string $changedBy): Business
    {
        return DB::transaction(function () use ($id, $newStatus, $changedBy) {
            $business = Business::findOrFail($id);
            $oldStatus = $business->status instanceof BusinessStatus ? $business->status->value : (string) $business->status;

            if ($oldStatus !== $newStatus) {
                $business->update(['status' => $newStatus]);

                BusinessStatusLog::create([
                    'business_id' => $business->id,
                    'old_status' => $oldStatus ?: 'unknown',
                    'new_status' => $newStatus,
                    'changed_by' => $changedBy,
                ]);

                Cache::forget('cockpit:merchants:metrics');
            }

            return $business;
        });
    }

    /**
     * Generate temporary impersonation token for merchant dashboard.
     */
    public function generateImpersonationToken(string $id, string $userId, string $adminId): string
    {
        $business = Business::findOrFail($id);
        $user = $business->users()->findOrFail($userId);

        $token = Str::random(64);

        Cache::put("impersonate:token:{$token}", [
            'user_id' => $user->id,
            'business_id' => $business->id,
            'admin_id' => $adminId,
            'created_at' => now()->timestamp,
        ], now()->addMinutes(2));

        return $token;
    }
}
