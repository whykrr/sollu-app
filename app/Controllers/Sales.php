<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\StockSalesModel;
use App\Libraries\TableFilter;
use App\Models\CustomerModel;

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

    public function export($type = 'sales', $filename = 'Penjualan')
    {
        $query = $this->request->getGet();

        if ($query['type_filter'] == 'monthly') {
            $filename .= ' Periode ' . formatMonthID($query['month']) . ' ' . $query['year'];
        } else if ($query['type_filter'] == 'daily') {
            $filename .= ' Produk Tanggal ' . formatDateID($query['date']);
        } else if ($query['type_filter'] == 'range') {
            $filename .= ' Tanggal ' . formatDateID($query['start_date']) . ' sd ' . formatDateID($query['end_date']);
        }
        $sales = new InvoiceStockSalesModel();
        $sales->select('invoice_stock_sales.*, 
        p.name as product_name,
        p.code as product_code,
        u.name as unit_name,
        ss.qty as qty,
        ss.price as price,
        ss.discount as dp,
        ss.sub_total as sub_total,
        DATE(invoice_stock_sales.date) as date_trans, 
        IF(invoice_stock_sales.payment_type=0, "Cash", "Kredit") as dt');
        if ($query['type_filter'] == 'daily') {
            $sales->where('DATE(invoice_stock_sales.date)', $query['date']);
        } elseif ($query['type_filter'] == 'range') {
            $sales->where('DATE(invoice_stock_sales.date) >=', $query['start_date'])
                ->where('DATE(invoice_stock_sales.date) <=', $query['end_date']);
        } elseif ($query['type_filter'] == 'monthly') {
            $sales->where('MONTH(invoice_stock_sales.date)', $query['month'])
                ->where('YEAR(invoice_stock_sales.date)', $query['year']);
        }

        if (@$query['customer'] != "") {
            $sales->where('invoice_stock_sales.customer_id', $query['customer']);
        }
        $sales->join('stock_sales ss', 'ss.invoice_id = invoice_stock_sales.id');
        $sales->join('products p', 'p.id = ss.product_id');
        $sales->join('units u', 'u.id = p.unit_id');
        $sales->where('type', $type);
        $sales->orderBy('invoice_stock_sales.created_at', 'ASC');
        $data = $sales->findAll();


        // remap data
        // reformat data
        $data = array_map(function ($item) {
            return [
                'invoice_no' => $item['invoice_no'],
                'date_trans' => formatDateSimple($item['date_trans']),
                'customer' => $item['customer'],
                'total' => formatIDR($item['total']),
                'discount' => $item['discount'],
                'grand_total' => formatIDR($item['grand_total']),
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'qty' => $item['qty'],
                'unit_name' => $item['unit_name'],
                'qty' => $item['qty'],
                'dp' => $item['dp'],
                'price' => $item['price'],
                'sub_total' => $item['sub_total'],
            ];
        }, $data);

        $format = [
            'parent' => [
                'marker' => 'invoice_no',
                'format' => "{invoice_no}   Tanggal: {date_trans}   Custommer: {customer}\nTotal: {total}   Discount: {discount}   Grand Total: {grand_total}",
            ],
            'child' => [
                ['label' => 'Kode', 'data' => 'product_code'],
                ['label' => 'Nama', 'data' => 'product_name'],
                ['label' => 'Qty', 'data' => 'qty'],
                ['label' => 'Satuan', 'data' => 'unit_name'],
                ['label' => 'Discount', 'data' => 'dp'],
                ['label' => 'Harga Jual', 'data' => 'price'],
                ['label' => 'Sub Total', 'data' => 'sub_total'],
            ],
        ];


        // $format = [
        //     ['label' => 'No', 'data' => 'increament'],
        //     ['label' => 'Kode', 'data' => 'invoice_no'],
        //     ['label' => 'Tanggal Transaksi', 'data' => 'date_trans'],
        //     ['label' => 'Total', 'data' => 'total'],
        //     ['label' => 'Diskon', 'data' => 'discount'],
        //     ['label' => 'Grand Total', 'data' => 'grand_total'],
        //     ['label' => 'Metode Pembayaran', 'data' => 'dt'],
        //     ['label' => 'Custommer', 'data' => 'customer'],
        // ];

        return Export::do($format, $data, $filename);
    }
}
