<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\StockSalesModel;
use App\Libraries\TableFilter;

class Sales extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'sales';
        $sales = new InvoiceStockSalesModel();

        $filter_year = $sales->getAvailableYear();
        if (count($filter_year) == 0) {
            $filter_year[0]['year'] = date('Y');
        }

        // get filter
        $filter_form = new TableFilter();
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('sales'), []);

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
