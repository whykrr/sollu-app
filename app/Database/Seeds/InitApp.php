<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitApp extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\App\FinancialAccount');
        $this->call('App\Database\Seeds\App\Setting');
    }
}
