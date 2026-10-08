<?php

use App\Models\OutletDevice;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
}, ['guards' => ['business', 'web']]);

Broadcast::channel('businesses.{id}', function ($user, $id) {
    return (string) $user->business_id === (string) $id;
}, ['guards' => ['business', 'web']]);

Broadcast::channel('outlets.{id}', function ($user, $id) {
    return $user->outlets()->where('outlets.id', $id)->exists();
}, ['guards' => ['business', 'web']]);

Broadcast::channel('outlet.{id}.pos', function ($user, $id) {
    if ($user instanceof OutletDevice) {
        return (string) $user->outlet_id === (string) $id && (bool) $user->is_active;
    }

    if ($user instanceof User) {
        return $user->outlets()->where('outlets.id', $id)->exists();
    }

    return false;
}, ['guards' => ['sanctum', 'business', 'web']]);
