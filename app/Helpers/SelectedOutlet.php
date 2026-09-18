<?php

namespace App\Helpers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class SelectedOutlet
{
    public const SESSION_KEY_PREFIX = 'selected_outlet_';

    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? Auth::user();
    }

    public static function make(?User $user = null): self
    {
        return new self($user);
    }

    /**
     * Get the active selected outlet object for the current session.
     * Auto-resolves if user only has access to 1 active outlet.
     */
    public function get(): ?Outlet
    {
        if (! $this->user) {
            return null;
        }

        $sessionKey = $this->getSessionKey();
        $storedValue = session()->get($sessionKey);

        if ($storedValue) {
            $outletId = null;

            if (is_string($storedValue)) {
                if (str_starts_with($storedValue, '{') || str_starts_with($storedValue, '[')) {
                    $decoded = json_decode($storedValue, true);
                    $outletId = is_array($decoded) && isset($decoded['id']) ? (string) $decoded['id'] : null;
                } else {
                    $outletId = $storedValue;
                }
            } elseif (is_array($storedValue) && isset($storedValue['id'])) {
                $outletId = (string) $storedValue['id'];
            } elseif (is_object($storedValue) && isset($storedValue->id)) {
                $outletId = (string) $storedValue->id;
            }

            if ($outletId && is_string($outletId) && \Illuminate\Support\Str::isUuid($outletId)) {
                $outlet = $this->findAccessibleOutlet($outletId);
                if ($outlet) {
                    if ($storedValue !== $outlet->id) {
                        session()->put($sessionKey, $outlet->id);
                    }

                    return $outlet;
                }
            }

            // If stored outlet is invalid, inactive, or inaccessible, clear session
            session()->forget($sessionKey);
        }

        // If user only has access to exactly 1 active outlet, auto-select it
        $accessibleOutlets = $this->getAccessibleOutletsQuery()->get();
        if ($accessibleOutlets->count() === 1) {
            return $accessibleOutlets->first();
        }

        return null;
    }

    /**
     * Get the selected outlet ID directly (or null if "Semua Outlet").
     */
    public function currentId(): ?string
    {
        return $this->get()?->id;
    }

    /**
     * Provide clean cached/shared data representation for frontend/Inertia props.
     */
    public function cached(): ?array
    {
        $outlet = $this->get();

        if (! $outlet) {
            return null;
        }

        return [
            'id' => $outlet->id,
            'name' => $outlet->name,
            'slug' => $outlet->slug,
            'timezone' => $outlet->timezone,
            'is_active' => (bool) $outlet->is_active,
            'is_stock_frozen' => (bool) ($outlet->is_stock_frozen ?? false),
        ];
    }

    /**
     * Change the selected outlet in session with security & tenant isolation validation.
     */
    public function change(string $outletId): Outlet
    {
        if (! $this->user) {
            abort(401, 'Unauthenticated.');
        }

        if (str_starts_with($outletId, '{') || str_starts_with($outletId, '[')) {
            $decoded = json_decode($outletId, true);
            if (is_array($decoded) && isset($decoded['id'])) {
                $outletId = (string) $decoded['id'];
            }
        }

        $outlet = $this->findAccessibleOutlet($outletId);

        if (! $outlet) {
            abort(403, 'Anda tidak memiliki akses ke outlet ini atau outlet tidak aktif.');
        }

        session()->put($this->getSessionKey(), $outlet->id);

        return $outlet;
    }

    /**
     * Reset selected outlet in session to "Semua Outlet".
     */
    public function all(): void
    {
        session()->forget($this->getSessionKey());
    }

    /**
     * Query accessible active outlets for the current user.
     */
    private function getAccessibleOutletsQuery(): Builder|BelongsToMany
    {
        if ($this->user->is_root_user) {
            return Outlet::query()
                ->where('is_active', true)
                ->where('business_id', $this->user->business_id);
        }

        return $this->user->outlets()
            ->where('outlets.is_active', true)
            ->where('outlets.business_id', $this->user->business_id);
    }

    /**
     * Find an active accessible outlet by ID.
     */
    private function findAccessibleOutlet(string $outletId): ?Outlet
    {
        if (! \Illuminate\Support\Str::isUuid($outletId)) {
            return null;
        }

        try {
            return $this->getAccessibleOutletsQuery()
                ->where('outlets.id', $outletId)
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSessionKey(): string
    {
        return self::SESSION_KEY_PREFIX.($this->user ? $this->user->id : 'guest');
    }
}
