<?php

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
