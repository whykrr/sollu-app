<?php

namespace App\Policies;

use App\Models\User;

class CMSPolicy
{
    public function user(?User $user)
    {
        return in_array($user->role, ['superadmin', 'admin']);
    }

    public function language(?User $user)
    {
        return $user->role == 'superadmin';
    }

    public function content_type(?User $user)
    {
        return $user->role == 'superadmin';
    }

    public function manage_content(?User $user)
    {
        return in_array($user->role, ['superadmin', 'admin', 'editor']);
    }

    public function message(?User $user)
    {
        return in_array($user->role, ['superadmin', 'admin', 'editor']);
    }

    public function setting(?User $user)
    {
        return in_array($user->role, ['superadmin', 'admin']);
    }

    public function setting_website(?User $user)
    {
        return in_array($user->role, ['superadmin']);
    }
}
