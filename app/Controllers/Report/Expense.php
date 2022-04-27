<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;

class Expense extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'report-expense';
        return view('report/expense/list', $data);
    }
}
