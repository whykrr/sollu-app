<?php

namespace App\Controllers\Inventory;

use App\Models\StockModel;
use App\Models\ProductsModel;
use App\Models\StockLogModel;
use App\Libraries\TableFilter;
use App\Models\FinancialModel;
use App\Models\StockSpoilModel;
use App\Controllers\BaseController;

class Stock_spoil extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'inventory-st-spoil';

        $filter_year[0]['year'] = date('Y');
        $filter_year[1]['year'] = date('Y') - 1;

        $filter_form = new TableFilter('dt_extend');
        $filter_form->setYear($filter_year);
        $data['filter_form'] = $filter_form->getFilter(base_url('inventory/stock_purchase'), []);
        return view('inventory/stock_spoil/list', $data);
    }

    /**
     * show form 
     */
    public function form()
    {
        // get model
        $model = new StockSpoilModel();
        $data = [];

        return view('inventory/stock_spoil/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new StockSpoilModel();
        $products = new ProductsModel();
        $stocks = new StockModel();
        $financials = new FinancialModel();
        $today = date('Y-m-d');

        // get data
        $data = $this->request->getPost();

        $data['product_id'] = intval($data['product_id']);

        //check qty < qty_old from $data
        if ($data['qty'] > $data['qty_old']) {
            $json = [
                "message" => "validation error",
                "validation_error" => [
                    "qty" => "Stok tidak cukup"
                ],
            ];

            return $this->respond($json, 400);
        }


        // get product
        $product = $products->find($data['product_id']);

        $spoil_data = [
            'date' => $today,
            'product_id' => $data['product_id'],
            'cogs' => $data['cogs'],
            'qty' => $data['qty'],
            'total' => $data['qty'] * $data['cogs'],
            'note' => $data['note'],
            'created_by' => user_id()
        ];
        $stock_data[0] = [
            'product_id' => $data['product_id'],
            'qty' => $data['qty'],
        ];
        $financial_data = [
            'account_id' => '2-3',
            'source' => 'spoil',
            'type' => 'out',
            'amount' => $spoil_data['total'],
            'description' => "Stok Terbuang $data[qty] $product[name] Catatan:$data[note]",
            'user_id' => user_id(),
        ];
        $product_update = [
            'id' => $data['product_id'],
            'stock' => $product['stock'] - $data['qty'],
        ];

        $db = \Config\Database::connect();
        $db->transBegin();

        // save data
        if (!$model->save($spoil_data)) {
            $db->transRollback();
            // get validation error message
            $errors = $model->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        // get last id
        $last_id = $model->getInsertID();
        $financial_data['reference_id'] = $last_id;

        // save financial
        if (!$financials->save($financial_data)) {
            $db->transRollback();
            $json = [
                "message" => implode(',', $financials->errors()),
            ];
            return $this->respond($json, 400);
        }

        // update product
        if (!$products->save($product_update)) {
            $db->transRollback();
            $json = [
                "message" => implode(',', $products->errors()) . "stock",
            ];

            return $this->respond($json, 400);
        }

        // update stock
        if (!$stocks->updateStockFIFO($stock_data, "Stok Terbuang ({$data['note']})")) {
            // rollback 
            $db->transRollback();
            $json = [
                "message" => implode(',', $products->errors()) . "stock",
            ];

            return $this->respond($json, 400);
        }

        $db->transCommit();

        log_event($financial_data['description'], $data);

        $json = [
            "message" => 'Data berhasil disimpan',
        ];

        // redirect to list
        return $this->respond($json, 200);
    }

    /**
     * delete data
     */
    public function delete($id)
    {
        // get model
        $model = new StockSpoilModel();
        $products = new ProductsModel();
        $stocks = new StockModel();
        $financials = new FinancialModel();

        $spoil = $model->find($id);
        $product = $products->find($spoil['product_id']);
        $stock = $stocks
            ->where('product_id', $spoil['product_id'])
            ->orderBy('created_at', 'desc')
            ->first();

        $update_product = [
            'id' => $spoil['product_id'],
            'stock' => $product['stock'] + $spoil['qty'],
        ];
        $insert_stock = [
            'product_id' => $spoil['product_id'],
            'stock_in' => $spoil['qty'],
            'cogs' => $spoil['cogs'],
            'selling_price' => $stock['selling_price'],
            'description' => "Penambahan stok dari hapus stok terbuang",
        ];
        $insert_stocks[] = $insert_stock;

        // trans begin
        $db = \Config\Database::connect();
        $db->transBegin();

        //delete financial
        if (!$financials->where('source', 'spoil')->where('reference_id', $id)->delete()) {
            $db->transRollback();

            $json = [
                "message" => "Data keuangan gagal dihapus",
            ];

            return $this->respond($json, 400);
        }

        // delete data
        if (!$model->delete($id)) {
            $db->transRollback();

            $json = [
                "message" => "Data gagal dihapus",
            ];

            return $this->respond($json, 400);
        }

        //update stock
        if (!$stocks->save($insert_stock)) {
            $db->transRollback();

            $json = [
                "message" => "Data stok gagal simpan",
            ];

            return $this->respond($json, 400);
        }

        // update product
        if (!$products->save($update_product)) {
            $db->transRollback();

            $json = [
                "message" => "Data produk gagal diubah",
            ];

            return $this->respond($json, 400);
        }

        if (!StockLogModel::StockIN('Hapus Stok Terbuang', $insert_stocks)) {
            $db->transRollback();

            $json = [
                "message" => "error stock log insert",
            ];

            return $this->respond($json, 500);
        }

        // commit
        $db->transCommit();

        log_event("Hapus Stok Terbuang", $spoil);

        $json = [
            "message" => 'Data berhasil dihapus',
        ];

        // redirect to list
        return $this->respond($json, 200);
    }
}
