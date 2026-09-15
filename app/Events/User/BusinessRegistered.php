<?php

namespace App\Events\User;

use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusinessRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly User $user,
        public readonly Outlet $outlet
    ) {}
}
