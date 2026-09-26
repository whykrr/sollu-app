<?php

namespace App\Helpers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SelectedOutlet
{
    public const SESSION_KEY_PREFIX = 'selected_outlet_';

    /**
     * In-memory request memoization cache for resolved outlets.
     *
     * @var array<string, ?Outlet>
     */
    private static array $resolvedCache = [];

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
     * Flush in-memory memoization cache (useful for testing or after switching outlets).
     */
    public static function flushMemoization(?string $userId = null): void
    {
        if ($userId) {
            foreach (array_keys(self::$resolvedCache) as $key) {
                if (str_starts_with($key, "{$userId}:")) {
                    unset(self::$resolvedCache[$key]);
                }
            }
        } else {
            self::$resolvedCache = [];
        }
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

        $userId = $this->user->id ?? 'guest';
        $sessionKey = $this->getSessionKey();
        $storedValue = session()->get($sessionKey);
        $memoKey = "{$userId}:".($storedValue ? (is_scalar($storedValue) ? (string) $storedValue : md5(serialize($storedValue))) : 'none');

        if (array_key_exists($memoKey, self::$resolvedCache)) {
            return self::$resolvedCache[$memoKey];
        }

        $resolved = $this->resolveOutlet($storedValue, $sessionKey);
        self::$resolvedCache[$memoKey] = $resolved;

        return $resolved;
    }

    /**
     * Resolve the outlet from session or auto-select if only 1 accessible.
     */
    private function resolveOutlet(mixed $storedValue, string $sessionKey): ?Outlet
    {
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

            if ($outletId && is_string($outletId) && Str::isUuid($outletId)) {
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
        $accessibleOutlets = $this->getAccessibleOutletsList();
        if (count($accessibleOutlets) === 1) {
            $first = $accessibleOutlets[0];

            return $this->hydrateOutletModel($first);
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
     * Resolve effective outlet ID based on sidebar master scope and request query param (Zero DB Query).
     */
    public static function resolveEffectiveOutletId(?User $user = null, ?string $requestedOutletId = null): ?string
    {
        $instance = self::make($user);
        $selectedOutlet = $instance->get();

        // 1. Sidebar Master Scope: Always prioritize active sidebar outlet
        if ($selectedOutlet) {
            return $selectedOutlet->id;
        }

        // 2. "Semua Outlet" Mode: Validate requested outlet in-memory
        if (! empty($requestedOutletId)) {
            $accessible = $instance->findAccessibleOutlet($requestedOutletId);

            return $accessible?->id;
        }

        return null;
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
        self::flushMemoization($this->user?->id);

        return $outlet;
    }

    /**
     * Reset selected outlet in session to "Semua Outlet".
     */
    public function all(): void
    {
        session()->forget($this->getSessionKey());
        self::flushMemoization($this->user?->id);
    }

    /**
     * Retrieve list of accessible outlets from SummaryUser cache if available, or database.
     *
     * @return array<int, array<string, mixed>|Outlet>
     */
    public function getAccessibleOutletsList(): array
    {
        if (! $this->user) {
            return [];
        }

        // 1. Try to read from SummaryUser cache first (0 DB query)
        $summary = SummaryUser::make($this->user)->cached();
        if (! empty($summary['outlets'])) {
            return $summary['outlets'];
        }

        // 2. Fallback to database query if cache not yet populated
        return $this->getAccessibleOutletsQuery()->get()->all();
    }

    /**
     * Convert array data or model into an Outlet Eloquent instance.
     */
    private function hydrateOutletModel(mixed $item): ?Outlet
    {
        if ($item instanceof Outlet) {
            return $item;
        }

        if (is_array($item) && isset($item['id'])) {
            $outlet = new Outlet;
            $outlet->forceFill([
                'id' => $item['id'],
                'name' => $item['name'] ?? '',
                'slug' => $item['slug'] ?? null,
                'timezone' => $item['timezone'] ?? config('app.timezone', 'Asia/Jakarta'),
                'is_active' => (bool) ($item['is_active'] ?? true),
                'is_stock_frozen' => (bool) ($item['is_stock_frozen'] ?? false),
                'business_id' => $this->user?->business_id,
            ]);
            $outlet->exists = true;

            return $outlet;
        }

        return null;
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
    public function findAccessibleOutlet(string $outletId): ?Outlet
    {
        if (! Str::isUuid($outletId)) {
            return null;
        }

        // 1. In-memory check against accessible outlet list
        $accessibleList = $this->getAccessibleOutletsList();
        foreach ($accessibleList as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item->id;
            if ($id === $outletId) {
                return $this->hydrateOutletModel($item);
            }
        }

        // 2. Direct database query fallback
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
