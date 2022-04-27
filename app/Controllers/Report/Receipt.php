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
        $data['sidebar_active'] = 'report-receipt';

        // instance model
        $stockSales = new StockSalesModel();
        $invoiceStockSales = new InvoiceStockSalesModel();
        $financial = new FinancialModel();
        $stockOut = new StockOutModel();

        $getTotalSales = $invoiceStockSales
            ->select('SUM(total) as total_sales')
            ->like('invoice_stock_sales.created_at', date('Y-m'), 'after')
            ->first();

        $getCogs = $stockOut
            ->select('SUM(cogs*qty) as total_cogs')
            ->like('created_at', date('Y-m'), 'after')
            ->first();

        $getExpences = $financial
            ->select('amount, description')
            ->like('created_at', date('Y-m'), 'after')
            ->where('account_id', '9-1')
            ->findAll();

        //sum total expenses
        $totalExpenses = 0;
        foreach ($getExpences as $exp) {
            $totalExpenses += $exp['amount'];
        }

        $data['sales'] = $getTotalSales['total_sales'];
        $data['cogs_sales'] = $getCogs['total_cogs'];
        $data['gross_profit'] = $data['sales'] - $data['cogs_sales'];
        $data['expenses'] = $getExpences;
        $data['total_expenses'] = $totalExpenses;
        $data['net_profit'] = $data['gross_profit'] - $totalExpenses;
        return view('report/list_receipt', $data);
    }
}
