<?php

namespace App\Helpers;

use App\Models\Outlet;
use App\Models\User;
use App\Services\Auth\UserPermissionCacheService;
use Illuminate\Support\Facades\Cache;

/**
 * @property Outlet $cached
 * @property Outlet $change
 *
 * @method Outlet|null get()
 */
class SummaryUser
{
    private $user;

    private $cache_key;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? request()->user();
        $this->cache_key = "auth:user:{$this->user?->id}:summary";
    }

    // static factory
    public static function make(?User $user = null): self
    {
        return new self($user);
    }

    public function cached()
    {
        $user = $this->user;

        if (! $user) {
            return [];
        }

        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () use ($user) {
                return [
                    'role' => $user->roles->map(fn ($role) => [
                        'name' => $role->name,
                        'label' => $role->label,
                    ])->toArray(),
                    'permissions' => app(UserPermissionCacheService::class)->getPermissions($user, $user->business_id),
                    'business' => $user->business ? array_merge(
                        $user->business->only('id', 'name', 'type', 'trial_end_at', 'logo', 'logo_url'),
                        [
                            'inventory_costing_method' => $user->business->getCostingMethod()->value,
                            'is_costing_configured' => $user->business->isCostingMethodConfigured(),
                        ]
                    ) : null,
                    'subscription' => $user->business->subscriptions()->with('plan')->where('status', 'active')->first()?->toArray()
                        ?? $user->business->subscriptions()->with('plan')->latest()->first()?->toArray(),
                    'features' => $user->business ? $user->business->activePlanFeatures() : [],
                    'outlets' => ($user->is_root_user
                        ? Outlet::where('business_id', $user->business_id)->where('is_active', true)
                        : $user->outlets()->where('outlets.is_active', true)->where('outlets.business_id', $user->business_id)
                    )
                        ->get()
                        ->map(fn ($outlet) => [
                            'id' => $outlet->id,
                            'name' => $outlet->name,
                            'slug' => $outlet->slug,
                            'timezone' => $outlet->timezone,
                            'is_active' => (bool) $outlet->is_active,
                            'is_stock_frozen' => (bool) ($outlet->is_stock_frozen ?? false),
                        ])
                        ->toArray(),
                    'has_pending_renewal_invoice' => $user->business->invoices()
                        ->where('status', 'open')
                        ->whereHas('items', function ($query) {
                            $query->where('item_type', 'plan_renewal');
                        })
                        ->exists(),
                ];
            }
        );
    }

    public static function cacheDelete(?string $user_id = null)
    {
        $instance = new self;
        $key = $user_id ? "auth:user:{$user_id}:summary" : $instance->cache_key;
        Cache::forget($key);
    }
}
