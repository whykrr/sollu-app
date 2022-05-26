<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\StockSalesModel;
use App\Models\StockOutModel;
use App\Models\FinancialModel;
use App\Models\ProductsModel;


class Receipt extends BaseController
{
    public function index()
    {
        // sidebar active
        $data['sidebar_active'] = 'report-receipt';

        // get filter
        $data['filter'] = $filter = $this->request->getPost();

        // instance model
        $stockSales = new StockSalesModel();
        $invoiceStockSales = new InvoiceStockSalesModel();
        $financial = new FinancialModel();

        // get available year
        $data['filter_year'] = $invoiceStockSales->getAvailableYear();
        if (count($data['filter_year']) == 0) {
            $data['filter_year'][0]['year'] = date('Y');
        }

        // get where
        $where['month'] = @$filter['month'] ?: date('m');
        $where['year'] = @$filter['year'] ?: date('Y');

        $getTotalSales = $invoiceStockSales
            ->select('SUM(grand_total) as total_sales')
            ->where('MONTH(invoice_stock_sales.created_at)', $where['month'])
            ->where('YEAR(invoice_stock_sales.created_at)', $where['year'])
            ->first();

        $getCogs = $stockSales
            ->select('SUM(cogs*qty) as total_cogs')
            ->where('MONTH(created_at)', $where['month'])
            ->where('YEAR(created_at)', $where['year'])
            ->get()
            ->getRowArray();

        $getExpences = $financial
            ->select('amount, description')
            ->where('MONTH(created_at)', $where['month'])
            ->where('YEAR(created_at)', $where['year'])
            ->where('account_id', '9-1')
            ->findAll();

        $getExpencesOther = $financial
            ->select('amount, description')
            ->where('MONTH(created_at)', $where['month'])
            ->where('YEAR(created_at)', $where['year'])
            ->where('account_id', '9-2')
            ->findAll();

        //sum total expenses
        $totalExpenses = 0;
        foreach ($getExpences as $exp) {
            $totalExpenses += $exp['amount'];
        }

        //sum total expenses other
        $totalExpensesOther = 0;
        foreach ($getExpencesOther as $exp) {
            $totalExpensesOther += $exp['amount'];
        }

        $data['sales'] = $getTotalSales['total_sales'];
        $data['cogs_sales'] = $getCogs['total_cogs'];
        $data['gross_profit'] = $data['sales'] - $data['cogs_sales'];
        $data['expenses'] = $getExpences;
        $data['total_expenses'] = $totalExpenses;
        $data['expenses_other'] = $getExpencesOther;
        $data['total_expenses_other'] = $totalExpensesOther;
        $data['net_profit'] = $data['gross_profit'] - $totalExpenses;
        return view('report/list_receipt', $data);
    }
}
