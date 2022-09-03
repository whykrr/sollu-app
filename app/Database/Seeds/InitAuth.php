<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitAuth extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\Auth\Permissions');
        $this->call('App\Database\Seeds\Auth\Groups');
        $this->call('App\Database\Seeds\Auth\PermissionGroup');
        $this->call('App\Database\Seeds\Auth\SuperAdmin');
    }
}
