<?php

namespace App\Support\Pulse;

use App\Models\CockpitUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Pulse\Contracts\ResolvesUsers;

class MultiGuardPulseUsers implements ResolvesUsers
{
    /**
     * The resolved users collection.
     *
     * @var Collection<int|string, Authenticatable>
     */
    protected Collection $resolvedUsers;

    /**
     * The custom field resolver.
     *
     * @var (callable(Authenticatable): array{name?: string, extra?: ?string, avatar?: ?string})|null
     */
    protected $fieldResolver = null;

    /**
     * Create a new MultiGuardPulseUsers instance.
     */
    public function __construct()
    {
        $this->resolvedUsers = collect();
    }

    /**
     * Return a unique key identifying the user.
     */
    public function key(Authenticatable $user): int|string|null
    {
        return $user->getAuthIdentifier();
    }

    /**
     * Eager load the users with the given keys from both User and CockpitUser models.
     *
     * @param  Collection<int, int|string|null>  $keys
     */
    public function load(Collection $keys): self
    {
        $uniqueKeys = $keys->filter()->unique()->values();

        if ($uniqueKeys->isEmpty()) {
            $this->resolvedUsers = collect();

            return $this;
        }

        // 1. Eager load tenant users
        $users = User::findMany($uniqueKeys);

        // 2. Identify missing keys and look up Cockpit users
        $foundUserKeys = $users->map(fn ($u) => (string) $this->key($u));
        $missingKeys = $uniqueKeys->reject(fn ($key) => $foundUserKeys->contains((string) $key))->values();

        $cockpitUsers = $missingKeys->isNotEmpty()
            ? CockpitUser::findMany($missingKeys)
            : collect();

        // 3. Merge resolved models
        $this->resolvedUsers = $users->concat($cockpitUsers);

        return $this;
    }

    /**
     * Find the user with the given key and format the output object.
     *
     * @return object{name: string, extra?: string, avatar?: string}
     */
    public function find(int|string|null $key): object
    {
        $user = $this->resolvedUsers->first(fn ($user) => (string) $this->key($user) === (string) $key);

        if ($this->fieldResolver !== null && $user !== null) {
            return (object) ($this->fieldResolver)($user);
        }

        return (object) [
            'name' => $user?->name ?? "ID: $key",
            'extra' => $user?->email ?? '',
            'avatar' => $user?->avatar ?? (($user?->email ?? false)
                ? sprintf('https://gravatar.com/avatar/%s?d=mp', hash('sha256', trim(strtolower($user->email))))
                : sprintf('https://gravatar.com/avatar?d=mp')
            ),
        ];
    }

    /**
     * Override the field resolver.
     *
     * @param  callable(Authenticatable): array{name?: string, extra?: ?string, avatar?: ?string}  $resolver
     */
    public function setFieldResolver(callable $resolver): self
    {
        $this->fieldResolver = $resolver;

        return $this;
    }
}
