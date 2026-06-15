<?php

namespace App\Models;

use App\Enum\RoleEnum;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $appends = [
        'label',
    ];

    public function getLabelAttribute(): string
    {
        return RoleEnum::tryFrom($this->name)?->label()
            ?? $this->name;
    }
}
