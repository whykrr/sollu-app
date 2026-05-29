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
class SelectedOutlet
{
    private $user;
    private $cache_key;

    public function __construct()
    {
        $this->user      = request()->user();
        $this->cache_key = "auth:user:{$this->user?->id}:selectedOutlet";
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

                if ($this->user->outlets()->count() === 1) {
                    $outlet = $this->user->business->outlets()->first();
                } else {
                    $outlet = null;
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

    public function get()
    {
        return Cache::get($this->cache_key, null);
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
