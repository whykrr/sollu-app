<?php

use App\Providers\AppServiceProvider;
use App\Providers\EloquentRedisUserProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PulseServiceProvider;
use Barryvdh\Debugbar\ServiceProvider;

return [
    AppServiceProvider::class,
    EloquentRedisUserProvider::class,
    HorizonServiceProvider::class,
    PulseServiceProvider::class,
    ServiceProvider::class,
];
