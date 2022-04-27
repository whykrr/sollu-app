<?php

namespace App\Controllers\Debtcredit;

use App\Controllers\BaseController;

class Debt extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'debtcredit-dept';
        return view('debtcredit/debt/list', $data);
    }
}
