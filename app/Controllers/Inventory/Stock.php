<?php

namespace App\Controllers\Inventory;

use App\Models\StockModel;
use App\Controllers\Export;
use App\Models\ProductsModel;
use App\Models\FinancialModel;
use App\Models\StockPurchaseModel;
use App\Controllers\BaseController;
use App\Models\ProductCategoriesModel;
use App\Models\InvoiceStockPurchaseModel;

class Stock extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $category = new ProductCategoriesModel();

        $data['sidebar_active'] = 'inventory-stock';
        $data['category'] = $category->findAll();
        return view('inventory/stock/list', $data);
    }

    /**
     * show page form
     */
    public function form()
    {
        $data['sidebar_active'] = 'inventory-stock';
        return view('inventory/stock/form', $data);
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

        $stockData = [];
        // remap stock
        foreach ($items as $key => $value) {
            $stockData[] = [
                'product_id' => $value['product_id'],
                'stock_in' => $value['qty'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
                'description' => 'Tambah Stok Manual pada ' . formatDateSimple($data['date']) . ' ' . $data['note'],
            ];
        }

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
        $stock = new StockModel();
        $product = new ProductsModel();
        $data = [];

        // get product detail
        $data['product'] = $product->findDetail($id);

        // get history stock
        $data['history_stock'] = $stock->getHistory($id);

        return view('inventory/stock/_detail', $data);
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $model = new ProductsModel();
        $data = $model->findAllDetail();

        $filename = 'Laporan Stok ' . formatDateID(date('Y-m-d'));

        $format = [
            ['label' => 'No', 'data' => 'increament'],
            ['label' => 'Kode', 'data' => 'code'],
            ['label' => 'Barcode', 'data' => 'barcode'],
            ['label' => 'Nama', 'data' => 'name'],
            ['label' => 'Kategori', 'data' => 'category_name'],
            ['label' => 'Satuan', 'data' => 'unit_name'],
            ['label' => 'Stok', 'data' => 'stock'],
            ['label' => 'Keterangan', 'data' => 'description'],
        ];

        return Export::do($format, $data, $filename);
    }
}
