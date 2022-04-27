<?php

namespace App\Controllers\Finance;

use App\Controllers\BaseController;
use App\Models\FinancialModel;
use App\Models\StockSalesModel;


class Income extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'finance-income';
        return view('finance/income/list', $data);
    }

    /**
     * show page detail
     */
    public function detail($id)
    {
        // get instance model
        $financialModel = new FinancialModel();
        $stockSales = new StockSalesModel();

        $data['data'] = $financialModel->getDetail($id);
        $data['product'] = $stockSales->getListSales($data['data']['reference_id']);
        return view('finance/income/_detail', $data);
    }
}
