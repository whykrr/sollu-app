<?php

namespace App\Auth;

use Cache;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class EloquentRedisUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier)
    {
        return Cache::remember(
            "auth:user:{$identifier}:info",
            3600, // cache 1 jam
            fn () => parent::retrieveById($identifier)
        );
    }

    /**
     * Update the "remember me" token in cache & DB.
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Hapus cache agar fresh
        Cache::forget("auth:user:{$user->getAuthIdentifier()}:info");

        parent::updateRememberToken($user, $token);
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user) {
            Cache::put("auth:user:{$user->getAuthIdentifier()}:info", $user, 3600);
        }

        return $user;
    }
}
