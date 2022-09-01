<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\StockModel;
use App\Models\FinancialModel;
use App\Models\StockSalesModel;
use App\Models\InvoiceStockSalesModel;
use App\Models\AccountReceivableModel;
use App\Models\CashierLogModel;
use App\Models\SettingModel;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class Cashier extends BaseController
{
    /**
     * index
     */
    public function index()
    {
        $cl = new CashierLogModel();
        $setting = new SettingModel();

        $data['sidebar_active'] = 'cashier';

        $data['cashier_log_id'] = $cl->getLog();
        $data['with_log'] = $setting->where('id', 'cashier_with_log')->first()['value'];
        return view('cashier/layout', $data);
    }

    /**
     * cashier panel
     */
    public function panel()
    {
        $cl = new CashierLogModel();
        $setting = new SettingModel();

        $data['blank'] = true;
        $data['cashier_log_id'] = $cl->getLog();
        $data['with_log'] = $setting->where('id', 'cashier_with_log')->first()['value'];

        return view('cashier/cashier', $data);
    }

    /**
     * Show page open cashier
     */
    public function start_cashier()
    {
        return view('cashier/_start');
    }

    /**
     * Store data open cashier log
     */
    public function store_log()
    {
        $cl = new CashierLogModel();

        $post = $this->request->getPost();

        // Set data to insert
        $data = [
            'date' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'begining_balance' => $post['begining_balance'],
            'start_by' => user_id(),
        ];

        // Insert data
        if (!$cl->save($data)) {
            // Respond with error
            $errors = $cl->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        // Respond success
        $json = [
            "message" => 'Data berhasil disimpan',
        ];
        return $this->respond($json, 200);
    }

    /**
     * Show page open cashier
     */
    public function end_cashier()
    {
        $cl = new CashierLogModel();
        $invoice = new InvoiceStockSalesModel();

        $data['data'] = $cl->getDataOpenToday();
        $data['total_transaction'] = $invoice->getSales('daily', date('Y-m-d'));
        $data['total_sales'] = $invoice->getIncome('daily', date('Y-m-d'));
        return view('cashier/_end', $data);
    }

    /**
     * Attemp end cashier
     */
    public function attemp_end_cashier()
    {
        $setting = new SettingModel();
        $cl = new CashierLogModel();

        $post = $this->request->getPost();

        // Set data to insert
        $data = [
            'id' => @$post['id'],
            'end_time' => date('H:i:s'),
            'ending_balance' => @$post['ending_balance'],
            'total_sales' => @$post['total_sales'],
            'end_by' => user_id(),
            'status' => 1,
        ];

        // Update data log
        $cl->save($data);

        // Get data setting
        $dept = $setting->find('outlet')['value'];
        $max_char = $setting->find('printer_max_length')['value'];

        // Create template for print
        $receipt = "";
        $receipt .= receipt_align("LAPORAN PENJUALAN", "center", $max_char) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        $receipt .= receipt_align("TANGGAL", "left", 12);
        $receipt .= receipt_align(": " . date("d/m/Y"), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("JAM TUTUP", "left", 12);
        $receipt .= receipt_align(": " . $data['end_time'], "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("KASIR", "left", 12);
        $receipt .= receipt_align(": " . user()->name, "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("DEPT", "left", 12);
        $receipt .= receipt_align(": $dept", "left", $max_char - 12) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        $receipt .= receipt_align("Saldo Awal", "left", 12);
        $receipt .= receipt_align(": " . intval(@$post['begining_balance']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Jml Trans", "left", 12);
        $receipt .= receipt_align(": " . intval(@$post['total_transaction']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Total", "left", 12);
        $receipt .= receipt_align(": " . intval(@$post['total_sales']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Tunai", "left", 12);
        $receipt .= receipt_align(": " . intval(@$post['total_sales']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Transfer", "left", 12);
        $receipt .= receipt_align(': 0', "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Saldo Akhir", "left", 12);
        $receipt .= receipt_align(": " . intval(@$post['ending_balance']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        // $receipt .= receipt_separator($max_char);
        // $receipt .= receipt_align("RETUR PENJUALAN", "left", 12) . receipt_align(': 0', 'right', $max_char - 12) . "\n";
        // $receipt .= receipt_align("Total", "left", 12) . receipt_align(': 0', 'right', $max_char - 12) . "\n";
        // $receipt .= receipt_separator($max_char);

        // Print
        $this->_print_receipt($receipt);

        // respond success
        $json = [
            "message" => 'Kasir berhasil ditutup',
        ];
        return $this->respond($json, 200);
    }

    public function print_end_cashier($id)
    {
        $setting = new SettingModel();
        $cl = new CashierLogModel();

        $data = $cl->find($id);

        $dept = $setting->find('outlet')['value'];
        $max_char = $setting->find('printer_max_length')['value'];

        // Create template for print
        $receipt = "";
        $receipt .= receipt_align("LAPORAN PENJUALAN", "center", $max_char) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        $receipt .= receipt_align("TANGGAL", "left", 12);
        $receipt .= receipt_align(": " . date("d/m/Y"), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("JAM TUTUP", "left", 12);
        $receipt .= receipt_align(": " . $data['end_time'], "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("KASIR", "left", 12);
        $receipt .= receipt_align(": " . user()->name, "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("DEPT", "left", 12);
        $receipt .= receipt_align(": $dept", "left", $max_char - 12) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        $receipt .= receipt_align("Saldo Awal", "left", 12);
        $receipt .= receipt_align(": " . intval(@$data['begining_balance']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Jml Trans", "left", 12);
        $receipt .= receipt_align(": " . intval(@$data['total_transaction']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Total", "left", 12);
        $receipt .= receipt_align(": " . intval(@$data['total_sales']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Tunai", "left", 12);
        $receipt .= receipt_align(": " . intval(@$data['total_sales']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Transfer", "left", 12);
        $receipt .= receipt_align(': 0', "left", $max_char - 12) . "\n";
        $receipt .= receipt_align("Saldo Akhir", "left", 12);
        $receipt .= receipt_align(": " . intval(@$data['ending_balance']), "left", $max_char - 12) . "\n";
        $receipt .= receipt_separator('-', $max_char);
        // $receipt .= receipt_separator($max_char);
        // $receipt .= receipt_align("RETUR PENJUALAN", "left", 12) . receipt_align(': 0', 'right', $max_char - 12) . "\n";
        // $receipt .= receipt_align("Total", "left", 12) . receipt_align(': 0', 'right', $max_char - 12) . "\n";
        // $receipt .= receipt_separator($max_char);

        // Print
        $this->_print_receipt($receipt);
    }

    /**
     * Save data transaction
     */
    public function save($print = true)
    {
        $error = "";

        // get data
        $data = $this->request->getPost();

        // Instace models
        $stock = new StockModel();
        $financial = new FinancialModel();
        $receivable = new AccountReceivableModel();
        $stockSales = new StockSalesModel();
        $invoice = new InvoiceStockSalesModel();
        $product = new ProductsModel();

        // Remapping data invoice_stock_sales
        $invoiceData = [
            'invoice_no' => $data['invoice_no'],
            'cashier_log_id' => $data['cashier_log_id'],
            'date' => $data['date'],
            'total' => $data['total'],
            'discount' => $data['discount'],
            'grand_total' => $data['grand_total'],
            'payment_type' => $data['payment_type'],
            'pay' => $data['pay'],
            'return' => $data['return'],
            'customer' => $data['customer'],
        ];

        // check if type is exist
        if (isset($data['type'])) {
            $invoiceData['type'] = $data['type'];
        }

        // check if note is exist
        if (isset($data['note'])) {
            $invoiceData['note'] = $data['note'];
        }


        // Remapping data stock_sales
        $stockSalesData = [];
        foreach ($data['items'] as $row) {
            $stockSalesData[] = [
                'product_id' => $row['product_id'],
                'qty' => $row['qty'],
                'cogs' => $row['cogs'],
                'price' => $row['price'],
                'discount' => $row['discount'],
                'sub_total' => $row['subtotal'],
            ];
        }

        // Get product updated
        $product_updated = $product->whereIn('id', array_column($data['items'], 'product_id'))->findAll();

        // Remapping data to update data product
        foreach ($product_updated as $key => $value) {
            // check product id
            foreach ($data['items'] as $keyItem => $valueItem) {
                if ($value['id'] == $valueItem['product_id']) {
                    $product_data[$key]['id'] = $value['id'];
                    $product_data[$key]['stock'] = $value['stock'] - $valueItem['qty'];
                }
            }
        }

        // Set data financial
        if ($data['payment_type'] == 0) {
            // Set data financial cash
            $financialData = [
                'account_id' => '1-2',
                'type' => 'in',
                'amount' => $data['grand_total'],
                'description' => 'Penjualan ' . $data['invoice_no'],
                'user_id' => user_id(),
            ];
        } else {
            // Set data receivable_account
            $receivableData = [
                'date' => $data['date'],
                'customer' => $data['customer'],
                'amount' => $data['grand_total'],
                'description' => 'Piutang dagang ' . $data['invoice_no'] . ' - ' . (isset($data['note']) ? $data['customer'] . ' ' . $data['note'] : $data['customer']),
                'due_date' => $data['duedate'],
                'status' => 0,
            ];
        }


        // instance builder library
        $db = \Config\Database::connect();
        //  Transaction begin
        $db->transBegin();

        // save invoice_stock_sales
        if (!$invoice->save($invoiceData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $invoice->errors());
        }

        /**
         * Stock
         */

        // Set invoice_id to stock_sales
        foreach ($stockSalesData as $key => $row) {
            $stockSalesData[$key]['invoice_id'] = $invoice->getInsertID();
        }

        // Save stock sales
        if (!$stockSales->insertBatch($stockSalesData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stockSales->errors());
        }

        // Update stock
        if (!$stock->updateStockFIFO($stockSalesData)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stock->errors());
        }

        // Update product
        if (!$product->updateStocks($product_data)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $product->errors());
        }



        /**
         * Financial
         */

        // Set reference_id

        // check payment
        if ($data['payment_type'] == 0) {

            $financialData['reference_id'] = $invoice->getInsertID();

            // Save financial
            if (!$financial->save($financialData)) {
                // rollback
                $db->transRollback();

                $error = implode(',', $financial->errors());
            }
        } else 
        if ($data['payment_type'] == 1) {

            $receivableData['invoice_id'] = $invoice->getInsertID();

            // save receivable_account
            if (!$receivable->save($receivableData)) {

                // rollback
                $db->transRollback();

                $error = implode(',', $receivable->errors());
            }
        }


        $json = [];

        // check error
        if ($error != "") {
            $json['message'] = $error;

            //return respond
            return $this->respond($json, 500);
        }

        // commit 
        $db->transCommit();


        // genrate text receipt
        $text = $this->_receipt($invoice->getInsertID());

        //print 
        if ($print) {
            $this->_print_receipt($text);
        }

        $json['message'] = 'success';
        return $this->respond($json, 200);
    }


    /**
     * Delete data transaction
     */
    public function delete($inv_id)
    {
        // load all model
        $stock = new StockModel();
        $stockSales = new StockSalesModel();
        $product = new ProductsModel();
        $invoice = new InvoiceStockSalesModel();
        $financial = new FinancialModel();
        $receivable = new AccountReceivableModel();

        // get data invoice
        $invoiceData = $invoice->where('id', $inv_id)->first();

        // Get Stock Sales
        $stockSalesData = $stockSales->where('invoice_id', $inv_id)->findAll();

        // Get product updated
        $product_updated = $product->whereIn('id', array_column($stockSalesData, 'product_id'))->findAll();

        // Mapping data to update data product
        $updateProduct = [];
        foreach ($product_updated as $key => $itemPU) {
            // check product id
            foreach ($stockSalesData as $keyItem => $itemSS) {
                if ($itemPU['id'] == $itemSS['product_id']) {
                    // push to array updateProduct
                    $updateProduct[] = [
                        'id' => $itemPU['id'],
                        'stock' => $itemPU['stock'] + $itemSS['qty'],
                    ];
                }
            }
        }

        // Mapping data to add stock
        $addStock = [];
        foreach ($stockSalesData as $key => $itemSS) {
            // push to array addStock
            $addStock[] = [
                'product_id' => $itemSS['product_id'],
                'stock_in' => $itemSS['qty'],
                'cogs' => $itemSS['cogs'],
                'selling_price' => $itemSS['price'],
                'description' => 'Hapus Penjualan ' . $invoiceData['invoice_no'],
            ];
        }

        // Transaction begin
        $error = "";
        $db = \Config\Database::connect();
        $db->transBegin();

        // Delete invoice stock sales
        if (!$invoice->delete($inv_id)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $invoice->errors());
        }

        // Delete stock sales
        if (!$stockSales
            ->where('invoice_id', $inv_id)
            ->delete()) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stockSales->errors());
        }

        // insert stock
        if (!$stock->insertBatch($addStock)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $stock->errors());
        }

        // update product
        if (!$product->updateStocks($updateProduct)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $product->errors());
        }

        // update product
        if (!$product->updateStocks($updateProduct)) {
            // rollback 
            $db->transRollback();

            $error = implode(',', $product->errors());
        }

        // check payment type
        if ($invoiceData['payment_type'] == 0) {
            // Delete financial
            if (!$financial
                ->where('reference_id', $inv_id)
                ->where('type', 'in')
                ->delete()) {
                // rollback 
                $db->transRollback();

                $error = implode(',', $financial->errors());
            }
        } else {
            // Delete receivable account
            if (!$receivable
                ->where('invoice_id', $inv_id)
                ->delete()) {
                // rollback 
                $db->transRollback();

                $error = implode(',', $receivable->errors());
            }
        }

        // check error
        if ($error != "") {
            $json['message'] = $error;

            //return respond
            return $this->respond($json, 500);
        }

        // commit
        $db->transCommit();


        $json['message'] = 'success';
        return $this->respond($json, 200);
    }

    /**
     * Get COGS stock_sales
     */
    public function get_cogs()
    {
        $stockSales = new StockSalesModel();

        $stockSales->getCOGSStockSales();

        echo "success!";
    }



    /**
     * Print
     */
    private function _print_receipt($text)
    {

        $setting = new SettingModel();
        $printerName = $setting->find('printer_name')['value'];
        $printerCutter = $setting->find('printer_cutter')['value'];

        if (!empty($printerName)) {
            if (!empty($printerName)) {
                $connector = new WindowsPrintConnector($printerName);
                $printer = new Printer($connector);
                $printer->text($text);
                $printer->feed(4);

                if ($printerCutter == 1) {
                    $printer->cut();
                }

                // open cash drawer
                $printer->pulse();
                $printer->close();
            }
        }
    }

    /**
     * Generate text receipt
     */
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
            $diskon_item = $ss['discount'] * $ss['qty'];

            $text .= receipt_align($ss['product_name'], "left", 32) . "\n";
            $text .= receipt_align(intval($ss['price']), "left", $max_char_price);
            $text .= receipt_align("x$ss[qty]", "left", $max_char_qty);
            $text .= receipt_align(intval($ss['sub_total'] + $diskon_item), "right", $max_char_sub) . "\n";
            if ($diskon_item > 0) {
                $text .= receipt_align("Potongan", "left", 13);
                $text .= receipt_align("-" . intval($diskon_item), "right", $max_char_sub) . "\n";
            }
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

    /**
     * Test printer
     */
    public function test_print()
    {
        $text = '-- TEST --';
        $this->_print_receipt($text);
    }
}
