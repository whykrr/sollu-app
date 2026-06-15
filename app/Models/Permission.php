<?php

namespace App\Models;

use App\Enum\PermissionEnum;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $appends = [
        'label',
    ];

    public function getLabelAttribute(): string
    {
        return PermissionEnum::tryFrom($this->name)?->label()
            ?? $this->name;
    }
}
