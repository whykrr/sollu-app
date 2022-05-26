<?php

namespace App\Database\Seeds\Auth;

use CodeIgniter\Database\Seeder;

class Groups extends Seeder
{
    public function run()
    {
        // instance db
        $db = \Config\Database::connect();

        // empty table auth_groups
        $db->table('auth_groups')->emptyTable();

        // reset auto increment
        $db->query('ALTER TABLE auth_groups AUTO_INCREMENT = 1');

        // create seeders table auth_groups
        $auth = service('authorization');

        $auth->createGroup('superadmin', 'Superadmin');
        $auth->createGroup('owner', 'Owner');
        $auth->createGroup('admin', 'Admin');
        $auth->createGroup('warehouse', 'Kepala Gudang');
        $auth->createGroup('user', 'Kasir');
    }
}
