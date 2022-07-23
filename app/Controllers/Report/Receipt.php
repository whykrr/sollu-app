<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\StockSalesModel;
use App\Models\StockOutModel;
use App\Models\FinancialModel;
use App\Models\ProductsModel;
use App\Libraries\TableFilter;


class Receipt extends BaseController
{
    public function index()
    {
        // sidebar active
        $data['sidebar_active'] = 'report-receipt';

        // get filter
        $filter = $this->request->getPost();

        // instance model
        $invoiceStockSales = new InvoiceStockSalesModel();

        // get available year
        $filter_year = $invoiceStockSales->getAvailableYear();
        if (count($filter_year) == 0) {
            $filter_year[0]['year'] = date('Y');
        }

        $report = $this->_getData($filter);

        //sum total expenses
        $totalExpenses = 0;
        foreach ($report['getExpences'] as $exp) {
            $totalExpenses += $exp['amount'];
        }

        //sum total expenses other
        $totalExpensesOther = 0;
        foreach ($report['getExpencesOther'] as $exp) {
            $totalExpensesOther += $exp['amount'];
        }

        $filter_form = new TableFilter();
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('report/receipt'), $filter, true);

        $data['sales'] = $report['getTotalSales']['total_sales'];
        $data['cogs_sales'] = $report['getCogs']['total_cogs'];
        $data['gross_profit'] = $data['sales'] - $data['cogs_sales'];
        $data['expenses'] = $report['getExpences'];
        $data['total_expenses'] = $totalExpenses;
        $data['expenses_other'] = $report['getExpencesOther'];
        $data['total_expenses_other'] = $totalExpensesOther;
        $data['net_profit'] = $data['gross_profit'] - ($totalExpenses + $totalExpensesOther);
        return view('report/list_receipt', $data);
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $filter = $this->request->getGet();
        $report = $this->_getData($filter);

        //sum total expenses
        $totalExpenses = 0;
        foreach ($report['getExpences'] as $exp) {
            $totalExpenses += $exp['amount'];
        }

        //sum total expenses other
        $totalExpensesOther = 0;
        foreach ($report['getExpencesOther'] as $exp) {
            $totalExpensesOther += $exp['amount'];
        }

        $data['sales'] = $report['getTotalSales']['total_sales'];
        $data['cogs_sales'] = $report['getCogs']['total_cogs'];
        $data['gross_profit'] = $data['sales'] - $data['cogs_sales'];
        $data['expenses'] = $report['getExpences'];
        $data['total_expenses'] = $totalExpenses;
        $data['expenses_other'] = $report['getExpencesOther'];
        $data['total_expenses_other'] = $totalExpensesOther;
        $data['net_profit'] = $data['gross_profit'] - ($totalExpenses + $totalExpensesOther);


        if ($filter['type_filter'] == 'monthly') {
            $name = 'Laporan Penjualan Periode ' . formatMonthID($filter['month']) . ' ' . $filter['year'];
        } elseif ($filter['type_filter'] == 'daily') {
            $name = 'Laporan Penjualan Tanggal ' . formatDateID($filter['date']);
        } elseif ($filter['type_filter'] == 'range') {
            $name = 'Laporan Penjualan Periode ' . formatDateID($filter['start_date']) . ' s/d ' . formatDateID($filter['end_date']);
        }


        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Laporan Penjualan');

        $worksheet = $spreadsheet->setActiveSheetIndex(0);
        $worksheet->mergeCells('A1:B1')->setCellValue('A1', $name)->getStyle('A1')->getFont()->setSize(14)->setBold(true);

        $cs = 2;
        $worksheet->setCellValue("A$cs", 'Penjualan');
        $worksheet->setCellValue("B$cs", $data['sales']);

        $cs++;
        $worksheet->setCellValue("A$cs", 'Harga Pokok Penjualan');
        $worksheet->setCellValue("B$cs", $data['cogs_sales']);

        $cs++;
        $worksheet->setCellValue("A$cs", 'Laba Kotor');
        $worksheet->setCellValue("B$cs", $data['gross_profit']);

        $cs++;
        $worksheet->mergeCells("A$cs:B$cs")->setCellValue("A$cs", 'Beban Operasional');

        foreach ($data['expenses'] as $exp) {
            $cs++;
            $worksheet->setCellValue("A$cs", "  $exp[description]");
            $worksheet->setCellValue("B$cs", $exp['amount']);
        }

        $cs++;
        $worksheet->setCellValue("A$cs", 'Total Beban Operasional');
        $worksheet->setCellValue("B$cs", $totalExpenses);

        $cs++;
        $worksheet->mergeCells("A$cs:B$cs")->setCellValue("A$cs", 'Beban Lainnya');

        foreach ($data['expenses_other'] as $exp_o) {
            $cs++;
            $worksheet->setCellValue("A$cs", "  $exp_o[description]");
            $worksheet->setCellValue("B$cs", $exp_o['amount']);
        }

        $cs++;
        $worksheet->setCellValue("A$cs", 'Total Beban Lainnya');
        $worksheet->setCellValue("B$cs", $totalExpensesOther);

        $cs++;
        $worksheet->setCellValue("A$cs", 'Laba Penjualan');
        $worksheet->setCellValue("B$cs", $data['net_profit']);

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$name.xlsx");

        return redirect()->to("$name.xlsx");
    }

    private function _getData($filter)
    {
        // instance model
        $stockSales = new StockSalesModel();
        $invoiceStockSales = new InvoiceStockSalesModel();
        $financial = new FinancialModel();

        $type =  @$filter['type_filter'] ?: 'monthly';

        // get where
        $where['month'] = @$filter['month'] ?: date('m');
        $where['year'] = @$filter['year'] ?: date('Y');
        $where['start_date'] = @$filter['start_date'] ?: date('Y-m-d');
        $where['end_date'] = @$filter['end_date'] ?: date('Y-m-d');
        $where['date'] = @$filter['date'] ?: date('Y-m-d');

        $data = [];
        $getTotalSales = $invoiceStockSales
            ->select('SUM(grand_total) as total_sales');
        if ($type == 'monthly') {
            $getTotalSales->where('MONTH(invoice_stock_sales.created_at)', $where['month'])
                ->where('YEAR(invoice_stock_sales.created_at)', $where['year']);
        } elseif ($type == 'daily') {
            $getTotalSales->where('DATE(invoice_stock_sales.created_at)', $where['date']);
        } elseif ($type == 'range') {
            $getTotalSales->where('DATE(invoice_stock_sales.created_at) >=', $where['start_date'])
                ->where('DATE(invoice_stock_sales.created_at) <=', $where['end_date']);
        }
        $data['getTotalSales'] = $getTotalSales->first();

        $getCogs = $stockSales
            ->select('SUM(cogs*qty) as total_cogs');
        if ($type == 'monthly') {
            $getCogs->where('MONTH(created_at)', $where['month'])
                ->where('YEAR(created_at)', $where['year']);
        } else if ($type == 'daily') {
            $getCogs->where('DATE(created_at)', $where['date']);
        } else if ($type == 'range') {
            $getCogs->where('DATE(created_at) >=', $where['start_date'])
                ->where('DATE(created_at) <=', $where['end_date']);
        }
        $data['getCogs'] = $getCogs->get()->getRowArray();

        $getExpences = $financial
            ->select('amount, description');
        if ($type == 'monthly') {
            $getExpences->where('MONTH(created_at)', $where['month'])
                ->where('YEAR(created_at)', $where['year']);
        } else if ($type == 'daily') {
            $getExpences->where('DATE(created_at)', $where['date']);
        } else if ($type == 'range') {
            $getExpences->where('DATE(created_at) >=', $where['start_date'])
                ->where('DATE(created_at) <=', $where['end_date']);
        }
        $getExpences->where('account_id', '9-1');
        $data['getExpences'] = $getExpences->findAll();

        $getExpencesOther = $financial
            ->select('amount, description');
        if ($type == 'monthly') {
            $getExpencesOther->where('MONTH(created_at)', $where['month'])
                ->where('YEAR(created_at)', $where['year']);
        } else if ($type == 'daily') {
            $getExpencesOther->where('DATE(created_at)', $where['date']);
        } else if ($type == 'range') {
            $getExpencesOther->where('DATE(created_at) >=', $where['start_date'])
                ->where('DATE(created_at) <=', $where['end_date']);
        }
        $getExpencesOther->where('account_id', '9-2');
        $data['getExpencesOther'] = $getExpencesOther->findAll();

        return $data;
    }
}
