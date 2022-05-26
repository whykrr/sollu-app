<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResetPermissions extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\Auth\Permissions');
        $this->call('App\Database\Seeds\Auth\PermissionGroup');
    }
}
