<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\StockModel;
use App\Models\FinancialModel;
use App\Models\StockSalesModel;
use App\Models\InvoiceStockSalesModel;
use App\Models\AccountReceivableModel;
use App\Models\SettingModel;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;


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
            'pay' => $data['pay'],
            'return' => $data['return'],
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

        // genrate text receipt
        $text = $this->_receipt($invoice->getInsertID());

        //print 
        $this->_print_receipt($text);

        $json['message'] = 'success';
        return $this->respond($json, 200);
    }

    private function _print_receipt($text)
    {

        $setting = new SettingModel();
        $printerName = $setting->find('printer_name')['value'];

        if (!empty($printerName)) {
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);
            $printer->text($text);
            $printer->cut();
            // open cash drawer
            $printer->pulse();
            $printer->close();
        }
    }

    private function _receipt($inv_id)
    {

        $setting = new SettingModel();
        $invoice = new InvoiceStockSalesModel();
        $stockSales = new StockSalesModel();

        $name = $setting->find('outlet')['value'];
        $address = $setting->find('outlet_address')['value'];
        $phone = $setting->find('outlet_phone')['value'];
        $receipt_note = $setting->find('receipt_note')['value'];

        $invoice_data = $invoice
            ->select('invoice_stock_sales.*, users.name as user_name')
            ->join('users', 'users.id = invoice_stock_sales.user_id')
            ->where('invoice_stock_sales.id', $inv_id)->first();

        $stockSales_data = $stockSales
            ->select('stock_sales.*, products.name as product_name')
            ->join('products', 'products.id = stock_sales.product_id')
            ->where('invoice_id', $inv_id)->findAll();

        $max_char = $setting->find('printer_max_length')['value'];
        $max_char_price = 10;
        $max_char_qty = 3;
        $max_char_sub = $max_char - ($max_char_price + $max_char_qty);

        $text = "";
        $text .= receipt_align($name, "center", 32);
        $text .= "\n";
        $text .= receipt_align_multiline($address, "center", 32);
        $text .= "\n";
        $text .= receipt_align("Telp. $phone", "center", 32);
        $text .= "\n";
        $text .= receipt_separator('-', 32);
        $text .= receipt_align("No", "left", 4) . ":" . receipt_align($invoice_data['invoice_no'], "right", 27) . "\n";
        $text .= receipt_align("Tgl", "left", 4) . ":" . receipt_align(formatDateTimeSimple($invoice_data['created_at']), "right", 27) . "\n";
        $text .= receipt_align("Ksr", "left", 4) . ":" . receipt_align($invoice_data['user_name'], "right", 27) . "\n";
        $text .= receipt_align("Pel", "left", 4) . ":" . receipt_align($invoice_data['customer'], "right", 27) . "\n";
        $text .= receipt_separator('-', 32);

        foreach ($stockSales_data as $key => $ss) {
            $text .= receipt_align($ss['product_name'], "left", 32) . "\n";
            $text .= receipt_align(intval($ss['price']), "left", $max_char_price);
            $text .= receipt_align("x$ss[qty]", "left", $max_char_qty);
            $text .= receipt_align(intval($ss['sub_total']), "right", $max_char_sub) . "\n";
        }

        $text .= receipt_separator('-', 32);
        $text .= receipt_align("Total", "left", 15) . receipt_align(intval($invoice_data['total']), "right", 17) . "\n";
        $text .= receipt_align("Diskon", "left", 15) . receipt_align(intval($invoice_data['discount']), "right", 17) . "\n";
        $text .= receipt_align("Grand Total", "left", 15) . receipt_align(intval($invoice_data['grand_total']), "right", 17) . "\n";
        $text .= receipt_align("Bayar", "left", 15) . receipt_align(intval($invoice_data['pay']), "right", 17) . "\n";
        $text .= receipt_repeater(' ', 15) . receipt_separator('-', 17);
        $text .= receipt_align("Kembalian", "left", 15) . receipt_align(intval($invoice_data['return']), "right", 17) . "\n";
        $text .= receipt_align("note : ", "left", 32) . "\n";
        $text .= receipt_align_multiline($receipt_note, "left", 32);

        return $text;
    }

    public function test_print()
    {
        $text = $this->_receipt(1);
        $this->_print_receipt($text);
    }

    public function test_receipt()
    {
        $text = $this->_receipt(1);
        $text = str_replace(' ', '&nbsp;', $text);

        echo "<tt>" . nl2br($text) . "</tt>";
    }
}
