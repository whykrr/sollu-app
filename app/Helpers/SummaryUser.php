<?php

namespace App\Helpers;

use App\Models\Outlet;
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

    public function __construct()
    {
        $this->user = request()->user();
        $this->cache_key = "auth:user:{$this->user?->id}:summary";
    }

    // static factory
    public static function make(): self
    {
        return new self;
    }

    public function cached()
    {
        $user = $this->user;

        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () use ($user) {
                return [
                    'role' => $user->roles->map(fn ($role) => [
                        'name' => $role->name,
                        'label' => $role->label,
                    ])->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'business' => $user->business,
                    'subscription' => $user->business->subscriptions()->with('plan')->latest()->first(),
                    'outlets' => $user->outlets()->where('is_active', '=', true)
                        ->get()
                        ->map(fn ($outlet) => $outlet->only('id', 'name')),
                ];
            }
        );
    }

    public static function cacheDelete()
    {
        $instance = new self;
        Cache::delete($instance->cache_key);
    }
}
