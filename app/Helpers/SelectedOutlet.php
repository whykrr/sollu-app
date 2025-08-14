<?php

namespace App\Helpers;

use App\Models\Outlet;
use Cache;

/**
 * @package App\Helpers
 *
 * @property Outlet $cached
 * @property Outlet $change
 */
class SelectedOutlet
{
    private $user;
    private $cache_key;

    public function __construct()
    {
        $this->user      = request()->user();
        $this->cache_key = "auth:user:{$this->user?->id}:outlet";
    }

    // static factory
    public static function make(): self
    {
        return new self();
    }

    public function cached()
    {
        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () {
                $outlet = $this->user->outlets()->first();

                if ($this->user->merchant->outlets()->count() === 1) {
                    $outlet = $this->user->merchant->outlets()->first();
                }

                return $outlet;
            }
        );
    }

    public function change($outlet_id)
    {
        Cache::delete($this->cache_key);

        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () use ($outlet_id) {
                return Outlet::find($outlet_id);
            }
        );
    }

    public function all()
    {
        Cache::forget($this->cache_key);

        return Cache::remember(
            $this->cache_key,
            60 * 60,
            function () {
                return null;
            }
        );
    }
}
