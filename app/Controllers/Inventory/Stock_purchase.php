<?php

namespace App\Controllers\Inventory;

use App\Models\StockModel;
use App\Controllers\Export;
use App\Models\ProductsModel;
use App\Models\StockLogModel;
use App\Models\SupplierModel;
use App\Libraries\TableFilter;
use App\Models\FinancialModel;
use App\Models\StockPurchaseModel;
use App\Controllers\BaseController;
use App\Models\InvoiceStockPurchaseModel;
use App\Database\Migrations\InvoiceStockPurchase;

class Stock_purchase extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'inventory-st-purchase';
        $isp = new InvoiceStockPurchaseModel();
        $supplier = new SupplierModel();

        // get data supplier
        $data['supplier'] = $supplier->findAll();

        $filter_year = $isp->getAvailableYear();
        if (count($filter_year) == 0) {
            $filter_year[0]['year'] = date('Y');
        }

        $filter_form = new TableFilter('dt_extend');
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('inventory/stock_purchase'), [], true);
        return view('inventory/stock_purchase/list', $data);
    }

    /**
     * show page form
     */
    public function form()
    {
        $data['sidebar_active'] = 'inventory-st-purchase';
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
        $supplier = new SupplierModel();

        // check invoice no if empty change to invoice_no_generate
        if ($data['invoice_no'] == "") {
            $data['invoice_no'] = $data['invoice_no_generate'];
        }

        // remap invoice
        $invoiceData = [
            'invoice_no' => $data['invoice_no'],
            'supplier_id' => $supplier->addIfNotExist($data['supplier_id'], $data['supplier']),
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
            'source' => 'purchase_stock',
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
                    $product_data[$key]['cogs'] = $valueItem['cogs'];
                    $product_data[$key]['selling_price'] = $valueItem['selling_price'];
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

        if (!StockLogModel::StockIN('Pembelian Stok ' . $data['invoice_no'], $stockData, $data['date'])) {
            $db->transRollback();

            $json = [
                "message" => "error stock log insert",
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

            log_event('Transaksi Pembelian Stok', $data);

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

    public function export()
    {
        $query = $this->request->getGet();
        $invoice = new InvoiceStockPurchaseModel();

        $filename = 'Pembelian Stok ';

        if ($query['type_filter'] == 'monthly') {
            $filename .= 'Periode ' . formatMonthID($query['month']) . ' ' . $query['year'];
        } else if ($query['type_filter'] == 'daily') {
            $filename .= 'Tanggal ' . formatDateID($query['date']);
        } else if ($query['type_filter'] == 'range') {
            $filename .= 'Tanggal ' . formatDateID($query['start_date']) . ' sd ' . formatDateID($query['end_date']);
        }

        $invoice->select('invoice_stock_purchases.*, p.code as product_code, p.name as product_name, sp.qty, sp.cogs, sp.selling_price, u.name as unit_name, DATE(invoice_stock_purchases.date) as date_pur');
        if ($query['type_filter'] == 'daily') {
            $invoice->where('DATE(invoice_stock_purchases.created_at)', $query['date']);
        } elseif ($query['type_filter'] == 'range') {
            $invoice->where('DATE(invoice_stock_purchases.created_at) >=', $query['start_date'])
                ->where('DATE(invoice_stock_purchases.created_at) <=', $query['end_date']);
        } elseif ($query['type_filter'] == 'monthly') {
            $invoice->where('MONTH(invoice_stock_purchases.created_at)', $query['month'])
                ->where('YEAR(invoice_stock_purchases.created_at)', $query['year']);
        }
        if (@$query['supplier'] != "") {
            $invoice->where('supplier_id', $query['supplier']);
        }
        $invoice->join('stock_purchases sp', 'sp.invoice_id = invoice_stock_purchases.id');
        $invoice->join('products p', 'p.id = sp.product_id');
        $invoice->join('units u', 'u.id = p.unit_id');
        $invoice->orderBy('invoice_stock_purchases.created_at', 'ASC');
        $data = $invoice->findAll();

        // reformat data
        $data = array_map(function ($item) {
            return [
                'invoice_no' => $item['invoice_no'],
                'date_pur' => formatDateSimple($item['date_pur']),
                'supplier' => $item['supplier'],
                'grand_total' => formatIDR($item['grand_total']),
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'qty' => $item['qty'],
                'unit_name' => $item['unit_name'],
                'qty' => $item['qty'],
                'cogs' => $item['cogs'],
                'selling_price' => $item['selling_price'],
            ];
        }, $data);

        $format = [
            'parent' => [
                'marker' => 'invoice_no',
                'format' => "{invoice_no}   Tanggal: {date_pur}\nSupplier: {supplier}   Total: {grand_total}",
            ],
            'child' => [
                ['label' => 'Kode', 'data' => 'product_code'],
                ['label' => 'Nama', 'data' => 'product_name'],
                ['label' => 'Qty', 'data' => 'qty'],
                ['label' => 'Satuan', 'data' => 'unit_name'],
                ['label' => 'Harga Beli', 'data' => 'cogs'],
                ['label' => 'Harga Jual', 'data' => 'selling_price'],
            ],
        ];

        return Export::do($format, $data, $filename);
    }
}
