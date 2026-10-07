<?php

namespace App\Events\Pos;

use App\Models\OutletDevice;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosDeviceUnpairedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OutletDevice $outletDevice,
        public ?User $unpairedBy = null
    ) {}
}
