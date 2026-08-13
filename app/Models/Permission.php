<?php

namespace App\Models;

use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPermission
 */
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
