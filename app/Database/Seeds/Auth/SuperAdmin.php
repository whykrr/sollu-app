<?php

namespace App\Database\Seeds\Auth;

use CodeIgniter\Database\Seeder;
use Myth\Auth\Entities\User;
use App\Models\UserModel;

class SuperAdmin extends Seeder
{
    public function run()
    {
        $users = model(UserModel::class);

        $users->emptyTable();

        // reset auto increment
        $users->query('ALTER TABLE users AUTO_INCREMENT = 1');

        // cridetial user
        $cridetial = [
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'password' => 'super4dm1n',
            'email' => 'superadmin',
        ];

        // instance entities
        $user = new User($cridetial);

        // activate user
        $user->activate();

        // add user to group
        $users = $users->withGroup('superadmin');

        // create user
        $users->save($user);
    }
}
