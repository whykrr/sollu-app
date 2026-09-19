<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'business_id',
        'label',
        'is_default',
    ];

    protected $appends = [
        'label',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function getLabelAttribute(): string
    {
        return $this->attributes['label']
            ?? RoleEnum::tryFrom($this->name)?->label()
            ?? $this->name;
    }
}
