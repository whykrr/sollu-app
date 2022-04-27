<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\StockModel;
use App\Models\FinancialModel;
use App\Models\StockSalesModel;
use App\Models\InvoiceStockSalesModel;
use App\Models\AccountReceivableModel;

class Cashier extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'cashier';
        return view('cashier/cashier', $data);
    }


    /**
     * show page open cashier
     */
    public function open_cashier()
    {
        $data['sidebar_active'] = 'cashier';
        return view('cashier/open', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        $error = "";

        // get data
        $data = $this->request->getPost();

        // instace models
        $stock = new StockModel();
        $financial = new FinancialModel();
        $receivable = new AccountReceivableModel();
        $stockSales = new StockSalesModel();
        $invoice = new InvoiceStockSalesModel();
        $product = new ProductsModel();

        // remmap data invoice
        $invoiceData = [
            'invoice_no' => $data['invoice_no'],
            'date' => $data['date'],
            'total' => $data['total'],
            'discount' => $data['discount'],
            'grand_total' => $data['grand_total'],
            'payment_type' => $data['payment_type'],
            'customer' => $data['customer'],
        ];

        $stockSalesData = [];
        foreach ($data['items'] as $row) {
            $stockSalesData[] = [
                'product_id' => $row['product_id'],
                'qty' => $row['qty'],
                'price' => $row['price'],
                'sub_total' => $row['subtotal'],
            ];
        }

        // get product updated
        $product_updated = $product->whereIn('id', array_column($data['items'], 'product_id'))->findAll();

        // remap product
        foreach ($product_updated as $key => $value) {
            // check product id
            foreach ($data['items'] as $keyItem => $valueItem) {
                if ($value['id'] == $valueItem['product_id']) {
                    $product_data[$key]['id'] = $value['id'];
                    $product_data[$key]['stock'] = $value['stock'] - $valueItem['qty'];
                }
            }
        }

        if ($data['payment_type'] == 0) {
            // remaap financial
            $financialData = [
                'account_id' => '1-2',
                'type' => 'in',
                'amount' => $data['grand_total'],
                'description' => 'Penjualan ' . $data['invoice_no'],
                'user_id' => user_id(),
            ];
        } else {
            // remaap financial
            $financialData = [
                'account_id' => '11-1',
                'type' => 'in',
                'amount' => $data['grand_total'],
                'description' => 'Piutang dagang ' . $data['invoice_no'],
                'user_id' => user_id(),
            ];

            $receivableData = [
                'date' => $data['date'],
                'customer' => $data['customer'],
                'amount' => $data['grand_total'],
                'description' => 'Piutang dagang ' . $data['invoice_no'] . ' - ' . $data['customer'],
                'due_date' => $data['duedate'],
                'status' => 0,
            ];
        }

        // instance builder library
        $db = \Config\Database::connect();

        // transaction begin
        $db->transBegin();

        // save invoice
        if (!$invoice->save($invoiceData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $invoice->errors());
        }

        /**
         * STOCK
         */
        // set invoice_id to stock sales
        foreach ($stockSalesData as $key => $row) {
            $stockSalesData[$key]['invoice_id'] = $invoice->getInsertID();
        }
        // save stock sales
        if (!$stockSales->insertBatch($stockSalesData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stockSales->errors());
        }

        //save stock
        if (!$stock->updateStockFIFO($stockSalesData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stock->errors());
        }

        // update stock product
        if (!$product->updateStocks($product_data)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $product->errors());
        }


        /**
         * FINANCIAL
         */
        $financialData['reference_id'] = $invoice->getInsertID();
        if (!$financial->save($financialData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $financial->errors());
        }

        // check if paymet type = 1
        if ($data['payment_type'] == 1) {
            $receivableData['invoice_id'] = $invoice->getInsertID();
            // save receivable
            if (!$receivable->save($receivableData)) {
                // rollback 
                $db->transRollback();

                $error = implode(',', $receivable->errors());
            }
        }

        // commit 
        $db->transCommit();

        $json = [];

        // check error
        if ($error != "") {
            $json['message'] = $error;

            //return respond
            return $this->respond($json, 500);
        }

        $json['message'] = 'success';
        return $this->respond($json, 200);
    }
}
