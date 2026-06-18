<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CockpitUser extends Authenticatable
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
}
