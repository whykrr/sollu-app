<?php

namespace App\Controllers\Inventory;

use App\Controllers\Cashier;
use App\Libraries\TableFilter;
use App\Models\StockSalesModel;
use App\Models\InvoiceStockSalesModel;

class Stock_out extends Cashier
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'inventory-stock-out';
        $sales = new InvoiceStockSalesModel();

        $filter_year = $sales->getAvailableYear();
        if (count($filter_year) == 0) {
            $filter_year[0]['year'] = date('Y');
        }

        // get filter
        $filter_form = new TableFilter();
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('inventory/stock_out'), []);

        return view('inventory/stock_out/list', $data);
    }

    /**
     * show page form
     */
    public function form()
    {
        $data['sidebar_active'] = 'inventory-stock-out';
        return view('inventory/stock_out/form', $data);
    }

    /**
     * save data
     */
    public function store()
    {
        // save transaction
        return $this->save(false);
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

        return view('inventory/stock_out/_detail', $data);
    }
}
