<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;

class Profit extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'report-profit';
        return view('report/profit/list', $data);
    }
}
