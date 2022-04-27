<?php

namespace App\Controllers\Inventory;

use App\Controllers\BaseController;
use App\Models\InvoiceStockPurchaseModel;
use App\Models\StockPurchaseModel;
use App\Models\StockModel;
use App\Models\ProductsModel;
use App\Models\FinancialModel;

class Stock_purchase extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'inventory-stock-purchase';
        return view('inventory/stock_purchase/list', $data);
    }

    /**
     * show page form
     */
    public function form()
    {
        $data['sidebar_active'] = 'inventory-stock-purchase';
        return view('inventory/stock_purchase/form', $data);
    }

    /**
     * save data
     */
    public function store()
    {
        $error = "";

        // get data
        $data = $this->request->getPost();

        // split items
        $itemsForm = explode(';', $data['items']);
        foreach ($itemsForm as $key => $value) {
            if (empty($value)) {
                break;
            }
            $item = explode(',', $value);
            $items[$key]['product_id'] = $item[0];
            $items[$key]['cogs'] = $item[1];
            $items[$key]['selling_price'] = $item[2];
            $items[$key]['qty'] = $item[3];
        }

        // instace models
        $product = new ProductsModel();
        $stock = new StockModel();
        $stock_purchase = new StockPurchaseModel();
        $invoice_stock_purchase = new InvoiceStockPurchaseModel();
        $financial = new FinancialModel();

        // check invoice no if empty change to invoice_no_generate
        if ($data['invoice_no'] == "") {
            $data['invoice_no'] = $data['invoice_no_generate'];
        }

        // remap invoice
        $invoiceData = [
            'invoice_no' => $data['invoice_no'],
            'supplier' => $data['supplier'],
            'date' => $data['date'],
            'total' => $data['total'],
            'discount' => 0,
            'grand_total' => $data['total'],
            'payment_type' => 0,
            'note' => $data['note'],
        ];

        $stockPurchaseData = [];
        //remap stock_purchase
        foreach ($items as $key => $value) {
            $stockPurchaseData[] = [
                'product_id' => $value['product_id'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
                'qty' => $value['qty'],
            ];
        }

        $stockData = [];
        // remap stock
        foreach ($items as $key => $value) {
            $stockData[] = [
                'product_id' => $value['product_id'],
                'stock_in' => $value['qty'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
                'description' => 'Pembelian ' . formatDateSimple($data['date']) . ' ' . $data['note'],
            ];
        }

        //remap financial
        $financialData = [
            'account_id' => '1-1',
            'type' => 'out',
            'amount' => $data['total'],
            'description' => 'Pembelian stok ' . $data['invoice_no'],
            'user_id' => user_id(),
        ];

        // get product updated
        $product_updated = $product->whereIn('id', array_column($items, 'product_id'))->findAll();

        // remap product
        foreach ($product_updated as $key => $value) {
            // check product id
            foreach ($items as $keyItem => $valueItem) {
                if ($value['id'] == $valueItem['product_id']) {
                    $product_data[$key]['id'] = $value['id'];
                    $product_data[$key]['stock'] = $value['stock'] + $valueItem['qty'];
                    $product_data[$key]['cogs'] = $value['cogs'];
                    $product_data[$key]['selling_price'] = $value['selling_price'];
                }
            }
        }

        // instance db
        $db = \Config\Database::connect();

        // transaction begin
        $db->transBegin();

        // save invoice
        if (!$invoice_stock_purchase->save($invoiceData)) {
            $db->transRollback();

            $errors = $invoice_stock_purchase->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        // set invoice id
        $invoice_id = $invoice_stock_purchase->insertID();

        // set invoice_id on $stockPurchaseData
        foreach ($stockPurchaseData as $key => $value) {
            $stockPurchaseData[$key]['invoice_id'] = $invoice_id;
        }

        // set invoice_id on $stockData
        foreach ($stockData as $key => $value) {
            $stockData[$key]['purchase_id'] = $invoice_id;
        }

        // set invoice_id on $financialData
        $financialData['reference_id'] = $invoice_id;

        // save stock_purchase
        if (!$stock_purchase->insertBatch($stockPurchaseData)) {
            $db->transRollback();

            $error = implode(',', $stock_purchase->errors());;
            $json = [
                "message" => $error,
            ];

            return $this->respond($json, 500);
        }

        // save stock
        if (!$stock->insertBatch($stockData)) {
            $db->transRollback();

            $error = implode(',', $stock->errors());;
            $json = [
                "message" => $error,
            ];

            return $this->respond($json, 500);
        }

        // update product
        if (!$product->updateStocks($product_data)) {
            $db->transRollback();

            $error = implode(',', $product->errors());;
            $json = [
                "message" => $error,
            ];

            return $this->respond($json, 500);
        }

        // save financial
        if (!$financial->save($financialData)) {
            $db->transRollback();

            $error = implode(',', $financial->errors());;
            $json = [
                "message" => $error,
            ];

            return $this->respond($json, 500);
        }

        // transaction commit
        if ($db->transStatus() === FALSE) {
            $db->transRollback();
            $json = [
                "message" => "transaction error",
            ];

            return $this->respond($json, 500);
        } else {
            $db->transCommit();
            $json = [
                "message" => "success",
            ];

            return $this->respond($json, 200);
        }
    }

    /**
     * show page detail
     */
    public function detail($id)
    {
        $invoice_stock_purchase = new InvoiceStockPurchaseModel();
        $stock_purchase = new StockPurchaseModel();
        $data = [];

        // get invoice stock purchase detail
        $data['data'] = $invoice_stock_purchase->find($id);

        // get stock purchase detail
        $data['detail'] = $stock_purchase->getByInvoice($id);

        return view('inventory/stock_purchase/_detail', $data);
    }
}
