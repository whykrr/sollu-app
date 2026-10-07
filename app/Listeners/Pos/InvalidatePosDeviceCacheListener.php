<?php

namespace App\Listeners\Pos;

use App\Events\Pos\PosDeviceUnpairedEvent;
use App\Services\Pos\PosDeviceAuthCacheService;

class InvalidatePosDeviceCacheListener
{
    public function __construct(
        public PosDeviceAuthCacheService $cacheService
    ) {}

    public function handle(PosDeviceUnpairedEvent $event): void
    {
        $this->cacheService->invalidate($event->outletDevice->id);
    }
}
