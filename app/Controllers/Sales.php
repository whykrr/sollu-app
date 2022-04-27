<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\StockSalesModel;

class Sales extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'finance-income';
        return view('sales/list', $data);
    }

    /**
     * show page detail
     */
    public function detail($id)
    {
        // get instance model
        $sales = new InvoiceStockSalesModel();
        $stock_sales = new StockSalesModel();

        $data['data'] = $sales->getDetail($id);
        $data['product'] = $stock_sales->getListSales($id);

        return view('sales/_detail', $data);
    }
}
