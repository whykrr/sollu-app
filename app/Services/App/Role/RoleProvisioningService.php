<?php

namespace App\Services\App\Role;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\RoleTemplateEnum;
use App\Models\Business;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleProvisioningService
{
    /**
     * Provision default role (Owner only) for a newly registered business.
     */
    public function provision(Business $business): void
    {
        setPermissionsTeamId($business->id);

        // Hanya Owner yang dibuat otomatis saat registrasi tenant baru
        $owner = Role::firstOrCreate(
            [
                'name' => RoleEnum::OWNER->value,
                'guard_name' => 'business',
                'business_id' => $business->id,
            ],
            [
                'label' => RoleEnum::OWNER->label(),
                'is_default' => true,
            ]
        );

        $owner->syncPermissions(PermissionEnum::values());
    }

    /**
     * Terapkan template peran POS siap pakai ke dalam bisnis tenant.
     */
    public function applyTemplate(Business $business, string $templateKey): Role
    {
        setPermissionsTeamId($business->id);

        $template = RoleTemplateEnum::tryFrom($templateKey);
        if (! $template) {
            throw new \InvalidArgumentException("Template peran '{$templateKey}' tidak ditemukan.");
        }

        $baseName = Str::slug($template->label());
        $roleName = $baseName;

        // Pastikan nama peran unik di level bisnis ini
        $count = 1;
        while (Role::where('business_id', $business->id)->where('name', $roleName)->exists()) {
            $roleName = $baseName.'-'.$count;
            $count++;
        }

        $role = Role::create([
            'business_id' => $business->id,
            'name' => $roleName,
            'label' => $template->label(),
            'guard_name' => 'business',
            'is_default' => false,
        ]);

        $role->syncPermissions($template->permissions());

        return $role;
    }
}
