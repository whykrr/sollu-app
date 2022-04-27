<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitApp extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\App\FinancialAccount');

        // check env
        $env = getenv('app.pos_type');
        if ($env == 'simple') {
            $this->call('App\Database\Seeds\App\SettingSimple');
        } else {
            $this->call('App\Database\Seeds\App\SettingComplex');
        }
    }
}
