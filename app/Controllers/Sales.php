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
        $filter_form = new TableFilter('dt_extend');
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('sales'), [], true);

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

    public function export()
    {
        $query = $this->request->getGet();
        $filename = 'Penjualan ';

        if ($query['type_filter'] == 'monthly') {
            $filename .= 'Periode ' . formatMonthID($query['month']) . ' ' . $query['year'];
        } else if ($query['type_filter'] == 'daily') {
            $filename .= 'Produk Tanggal ' . formatDateID($query['date']);
        } else if ($query['type_filter'] == 'range') {
            $filename .= 'Tanggal ' . formatDateID($query['start_date']) . ' s/d ' . formatDateID($query['end_date']);
        }
        $sales = new InvoiceStockSalesModel();
        $sales->select('*, DATE(date) as date_trans, IF(payment_type=0, "Cash", "Kredit") as dt');
        if ($query['type_filter'] == 'daily') {
            $sales->where('DATE(created_at)', $query['date']);
        } elseif ($query['type_filter'] == 'range') {
            $sales->where('DATE(created_at) >=', $query['start_date'])
                ->where('DATE(created_at) <=', $query['end_date']);
        } elseif ($query['type_filter'] == 'monthly') {
            $sales->where('MONTH(created_at)', $query['month'])
                ->where('YEAR(created_at)', $query['year']);
        }
        $sales->where('type', 'sales');
        $data = $sales->findAll();

        // dd($data);

        $format = [
            ['label' => 'No', 'data' => 'increament'],
            ['label' => 'Kode', 'data' => 'invoice_no'],
            ['label' => 'Tanggal Transaksi', 'data' => 'date_trans'],
            ['label' => 'Total', 'data' => 'total'],
            ['label' => 'Diskon', 'data' => 'discount'],
            ['label' => 'Grand Total', 'data' => 'grand_total'],
            ['label' => 'Metode Pembayaran', 'data' => 'dt'],
            ['label' => 'Custommer', 'data' => 'customer'],
        ];

        return Export::do($format, $data, $filename);
    }
}
