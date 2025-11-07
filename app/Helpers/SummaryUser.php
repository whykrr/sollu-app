<?php

namespace App\Helpers;

use App\Models\Outlet;
use Illuminate\Support\Facades\Cache;

/**
 * @package App\Helpers
 *
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
        $this->user      = request()->user();
        $this->cache_key = "auth:user:{$this->user?->id}:summary";
    }

    // static factory
    public static function make(): self
    {
        return new self();
    }

    public function cached()
    {
        $user = $this->user;

        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () use ($user) {
                $outlets = $user->outlets()->where('is_active', '=', true)->get()
                    ->map(fn ($outlet) => $outlet->only('id', 'name'));
                if (count($outlets) === 0) {
                    $outlets = $user->merchant->outlets()
                        ->where('is_active', '=', true)->get()
                        ->map(fn ($outlet) => $outlet->only('id', 'name'));
                }

                return [
                    'role'         => $user->roles()->pluck('label', 'name')->toArray(),
                    'permissions'  => $user->getAllPermissions()->pluck('name')->toArray(),
                    'merchant'     => $user->merchant,
                    'subscription' => $user->merchant->subscriptions()->with('plan')->latest()->first(),
                    'outlets'      => $outlets,
                ];
            }
        );
    }

    public static function cacheDelete()
    {
        $instance = new self();
        Cache::delete($instance->cache_key);
    }
}
