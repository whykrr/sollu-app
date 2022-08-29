<?php

namespace App\Controllers\Inventory;

use App\Controllers\BaseController;

class Stock_opname extends BaseController
{
    public function index()
    {
    }

    public function import_excel($file)
    {
        // instance model product
        $product = new \App\Models\ProductsModel();

        //instance model stock
        $stock = new \App\Models\StockModel();

        // import file excel
        $data = $this->_read_excel($file);

        $stock_change = [];

        foreach ($data as $key => $p) {
            if ($key == 0) {
                continue;
            }

            $stock_change[] = [
                'code' => $p[1],
                'stock' => $p[6],
            ];
        }


        $products = $product->whereIn('code', array_column($stock_change, 'code'))->findAll();

        $update_stock_product = [];
        $add_stock = [];
        $update_stock_fifo = [];

        foreach ($products as $p) {
            // get key stock change by code
            $get_key_sc = array_search($p['code'], array_column($stock_change, 'code'));
            $stok_real = $stock_change[$get_key_sc]['stock'];

            if ($p['stock'] > $stok_real) {
                $update_stock_product[] = [
                    'id' => $p['id'],
                    'stock' => $stok_real,
                ];

                $update_stock_fifo[] = [
                    'product_id' => $p['id'],
                    'qty' => $p['stock'] - $stok_real,
                ];
            } else if ($p['stock'] < $stok_real) {
                $update_stock_product[] = [
                    'id' => $p['id'],
                    'stock' => $stok_real,
                ];

                $add_stock[] = [
                    'product_id' => $p['id'],
                    'stock_in' => $stok_real - $p['stock'],
                    'stock_out' => 0,
                    'cogs' => $p['cogs'],
                    'selling_price' => $p['selling_price'],
                    'description' => 'Stock Opname ' . date('d/m/Y'),
                ];
            }
        }

        d($products);
        d($update_stock_product);
        d($add_stock);
        d($update_stock_fifo);

        if (count($update_stock_product) > 0) {
            $product->updateStocks($update_stock_product, 'id');
        }

        // insert add stock if exist
        if (count($add_stock) > 0) {
            $stock->insertBatch($add_stock);
        }

        // update fifo if exist
        if (count($update_stock_fifo) > 0) {
            $stock->updateStockFIFO($update_stock_fifo);
        }
    }

    public function _read_excel($file)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);

        // $read = new \PhpOffice\PhpSpreadsheet\Reader\IReadFilter();

        return ($spreadsheet->getActiveSheet()->toArray());
    }
}
