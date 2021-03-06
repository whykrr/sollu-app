<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\StockSalesModel;
use App\Libraries\TableFilter;

class Product extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'report-product';

        // get filter
        $filter = $this->request->getPost();

        // instance model
        $stockSales = new StockSalesModel();

        // get available year
        $filter_year = $stockSales->getAvailableYear();
        if (count($filter_year) == 0) {
            $filter_year[0]['year'] = date('Y');
        }

        $filter_form = new TableFilter();
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('report/product'), $filter, true);

        // get filter

        $data['report'] = $this->_getData($filter);

        return view('report/list_product', $data);
    }

    /**
     * get data report
     */
    public function _getData($filter)
    {
        // instance model
        $products = new ProductsModel();

        $type =  @$filter['type_filter'] ?: 'monthly';

        // get where
        $where['month'] = @$filter['month'] ?: date('m');
        $where['year'] = @$filter['year'] ?: date('Y');
        $where['start_date'] = @$filter['start_date'] ?: date('Y-m-d');
        $where['end_date'] = @$filter['end_date'] ?: date('Y-m-d');
        $where['date'] = @$filter['date'] ?: date('Y-m-d');

        // get data
        $get_data = $products
            ->select('products.*, units.name as unit_name, SUM(stock_sales.qty) as total_sales')
            ->join('stock_sales', 'stock_sales.product_id = products.id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->orderBy('total_sales', 'DESC')
            ->groupBy('products.id');
        if ($type == 'daily') {
            $get_data->where('DATE(stock_sales.created_at)', $where['date']);
        } elseif ($type == 'range') {
            $get_data->where('DATE(stock_sales.created_at) >=', $where['start_date'])
                ->where('DATE(stock_sales.created_at) <=', $where['end_date']);
        } elseif ($type == 'monthly') {
            $get_data->where('MONTH(stock_sales.created_at)', $where['month'])
                ->where('YEAR(stock_sales.created_at)', $where['year']);
        }
        $get_data = $get_data->findAll();

        return $get_data;
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $filter = $this->request->getGet();
        $data = $this->_getData($filter);

        if ($filter == 'monthly') {
            $name = 'Laporan Penjualan Produk Periode ' . formatMonthID($filter['month']) . ' ' . $filter['year'];
        } else if ($filter == 'daily') {
            $name = 'Laporan Penjualan Produk Tanggal ' . formatDateID($filter['date']);
        } else if ($filter == 'range') {
            $name = 'Laporan Penjualan Produk Tanggal ' . formatDateID($filter['start_date']) . ' s/d ' . formatDateID($filter['end_date']);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Laporan Penjualan Produk');

        $worksheet = $spreadsheet->setActiveSheetIndex(0);
        $worksheet->setCellValue('A1', 'No');
        $worksheet->setCellValue('B1', 'Produk');
        $worksheet->setCellValue('C1', 'Satuan');
        $worksheet->setCellValue('D1', 'Harga Jual');
        $worksheet->setCellValue('E1', 'Total Penjualan');

        // $row_start = 2;
        foreach ($data as $key => $value) {
            $worksheet->setCellValue('A' . ($key + 2), $key + 1);
            $worksheet->setCellValue('B' . ($key + 2), $value['name']);
            $worksheet->setCellValue('C' . ($key + 2), $value['unit_name']);
            $worksheet->setCellValue('D' . ($key + 2), $value['selling_price']);
            $worksheet->setCellValue('E' . ($key + 2), $value['total_sales']);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$name.xlsx");

        return redirect()->to("$name.xlsx");
    }
}
