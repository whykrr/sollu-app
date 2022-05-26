<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\StockSalesModel;

class Product extends BaseController
{
    public function index()
    {
        $data['filter'] = [];
        $data['sidebar_active'] = 'report-product';

        // get filter
        $filter = $this->request->getPost();
        $data['filter'] = $filter;

        // instance model
        $stockSales = new StockSalesModel();

        // get available year
        $data['filter_year'] = $stockSales->getAvailableYear();
        if (count($data['filter_year']) == 0) {
            $data['filter_year'][0]['year'] = date('Y');
        }

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

        // get where
        $where['month'] = @$filter['month'] ?: date('m');
        $where['year'] = @$filter['year'] ?: date('Y');

        // get data
        return $products
            ->select('products.*, units.name as unit_name, SUM(stock_sales.qty) as total_sales')
            ->join('stock_sales', 'stock_sales.product_id = products.id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->orderBy('total_sales', 'DESC')
            ->groupBy('products.id')
            ->where('MONTH(stock_sales.created_at)', $where['month'])
            ->where('YEAR(stock_sales.created_at)', $where['year'])
            ->findAll();
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $filter = $this->request->getGet();
        $data = $this->_getData($filter);

        $name = 'Laporan Penjualan Produk Periode ' . formatMonthID($filter['month']) . ' ' . $filter['year'];

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
