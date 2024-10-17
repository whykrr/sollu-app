<?php

namespace App\Controllers;

use Throwable;
use App\Models\StockModel;
use App\Models\SettingModel;
use App\Models\StockLogModel;
use App\Models\StockSpoilModel;
use App\Controllers\BaseController;
use App\Models\InvoiceStockSalesModel;
use App\Models\InvoiceStockPurchaseModel;
use App\Models\ProductsModel;

class Setting extends BaseController
{
    public function index()
    {
        // reset all cache

        $sesion = session();

        // hard load if update success
        if ($sesion->getFlashdata('update-success')) {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        }

        $setting = new SettingModel();

        $data['sidebar_active'] = 'setting';
        $data['data'] = $setting->findAll();
        $data['category'] = [];

        // group category setting
        foreach ($data['data'] as $key => $value) {
            // check category if not in array
            if (!in_array($value['category'], $data['category'])) {
                // push to array
                array_push($data['category'], $value['category']);
            }
        }

        return view('setting/index', $data);
    }

    /**
     * Save setting
     */
    public function save()
    {
        $setting = new SettingModel();

        $data = $this->request->getPost();
        $saveData = [];

        foreach ($data['id'] as $key => $value) {
            $saveData[] = [
                'id' => $value,
                'value' => $data['value'][$key]
            ];
        }

        $setting->updateBatch($saveData, 'id');

        return redirect()->to('/setting');
    }

    public function update()
    {
        $rootPath = ROOTPATH;
        $fcPath = ROOTPATH;

        // check if os windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $rootPath = str_replace('\\', '/', $rootPath);
            $fcPath = str_replace('\\', '/', $fcPath);
        }

        exec('cd ' . $rootPath . ' && git pull', $result);

        // check if git pull success
        if (strpos($result[0], 'Already up to date.') !== false) {
            return redirect()->to('/setting')->with('update-error', 'Already up to date.');
        }

        // check dir update-log exist or not
        if (!file_exists($rootPath . 'update-log')) {
            mkdir($rootPath . 'update-log', 0777, true);
        }

        // update composer
        exec('cd ' . $rootPath . ' && composer install', $result_composer);

        $date_update = date('ymdhis');
        // create file on update directory
        $file = fopen($rootPath . 'update-log/update-' . $date_update . '.txt', 'w');
        fwrite($file,  "ROOTPATH " . $rootPath . PHP_EOL);
        fwrite($file,  "FCPATH " . $fcPath . PHP_EOL);
        fwrite($file,  "Update on " . date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($file,  "-- Pulling file --" . PHP_EOL);
        foreach ($result as $line) {
            fwrite($file, $line . PHP_EOL);
        }

        fwrite($file,  "-- Composer Install --" . PHP_EOL);
        foreach ($result_composer as $line_c) {
            fwrite($file, $line_c . PHP_EOL);
        }

        $seeder = \Config\Database::seeder();
        $migrate = \Config\Services::migrations();
        $db = \Config\Database::connect();

        try {
            fwrite($file, PHP_EOL . "-- Migrate Executed Migration --" . PHP_EOL);
            $migrate->latest();
            // get data from table migration
            $migrates = $db->table('migrations')->get()->getResultArray();
            foreach ($migrates as $m) {
                fwrite($file, $m['class'] . PHP_EOL);
            }
        } catch (Throwable $e) {
            // throw $th;
            fwrite($file, PHP_EOL . "-- Migrate Error --" . PHP_EOL);
            fwrite($file, $e->getMessage() . PHP_EOL);
        }
        fwrite($file, PHP_EOL . "-- Run DB Seed --" . PHP_EOL);
        fwrite($file, "ResetPermissions" . PHP_EOL);
        $seeder->call('ResetPermissions');

        fwrite($file, PHP_EOL . "-- Copy Asset --" . PHP_EOL);
        $this->copyDirectory($rootPath . 'public/assets', FCPATH . 'assets', $result_asset);
        foreach ($result_asset as $line_asset) {
            fwrite($file, $line_asset . PHP_EOL);
        }

        $this->copyDirectory($rootPath . 'public/css', FCPATH . 'css', $result_css);
        foreach ($result_css as $line_css) {
            fwrite($file, $line_css . PHP_EOL);
        }

        $this->copyDirectory($rootPath . 'public/js', FCPATH . 'js', $result_js);
        foreach ($result_js as $line_js) {
            fwrite($file, $line_js . PHP_EOL);
        }

        fclose($file);

        //redirect with data
        return redirect()->to('/setting')->with('update-success', 'Update Successfuly');
    }

    // copy and replace all file in directory
    public function copyDirectory($src, $dst, &$result = [])
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file, $result);
                } else {
                    if (copy($src . '/' . $file, $dst . '/' . $file)) {
                        $result[] = $dst . '/' . $file . ' copied';
                    } else {
                        $result[] = $dst . '/' . $file . ' failed';
                    }
                }
            }
        }
        closedir($dir);
        return $result;
    }

    public function init_stock_log()
    {
        $stockLog = new StockLogModel();
        $stock = new StockModel();
        $invoiceStockPurchase = new InvoiceStockPurchaseModel();
        $invoiceStockSales = new InvoiceStockSalesModel();
        $spoil = new StockSpoilModel();

        $stockLog->truncate();

        //tambah stok manual
        $tambahStokManual = $stock->like('description', 'Tambah Stok Manual', 'both')->findAll();

        $stockLogStockManual = [];
        foreach ($tambahStokManual as $key => $value) {
            // remove specific string
            $date_add = str_replace('Tambah Stok Manual pada ', '', $value['description']);

            // get 10 digit from string
            $date_add = str_replace('/', '-', substr($date_add, 0, 10));

            $stockLogStockManual[] = [
                'description' => "Tambah Stok Manual",
                'product_id' => $value['product_id'],
                'datetime' => date('Y-m-d H:i:s', strtotime($date_add)),
                'stock_in' => $value['stock_in'],
                'stock_out' => 0,
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        $stockLog->insertBatch($stockLogStockManual);

        // Hapus Penjualan
        $tambahStokManual = $stock->like('description', 'Hapus Penjualan', 'both')->findAll();

        $stockLogStockManual = [];
        foreach ($tambahStokManual as $key => $value) {
            $act = $value['description'];
            if (strpos($value['description'], 'OUT') !== false) {
                $act = str_replace('Pembelian', 'Stok Keluar', $value['description']);
            }

            $stockLogStockManual[] = [
                'description' => $act,
                'product_id' => $value['product_id'],
                'datetime' => date("Y-m-d H:i:s", strtotime("$value[created_at] +1 minutes")),
                'stock_in' => $value['stock_in'],
                'stock_out' => 0,
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];

            $stockLogStockManual[] = [
                'description' => str_replace('Hapus ', '', $act),
                'product_id' => $value['product_id'],
                'datetime' => $value['created_at'],
                'stock_in' => 0,
                'stock_out' => $value['stock_in'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        $stockLog->insertBatch($stockLogStockManual);

        //Pembelian Stok
        $invoiceStockPurchase = $invoiceStockPurchase
            ->join('stock_purchases', 'stock_purchases.invoice_id = invoice_stock_purchases.id')
            ->findAll();

        $stockLogStockPurchase = [];
        foreach ($invoiceStockPurchase as $key => $value) {
            $stockLogStockPurchase[] = [
                'description' => "Pembelian Stok $value[invoice_no]",
                'product_id' => $value['product_id'],
                'datetime' => $value['date'],
                'stock_in' => $value['qty'],
                'stock_out' => 0,
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        // insert batch per 150 data
        $stockLog->insertBatch($stockLogStockPurchase, null, 150);

        //Penjualan Stok
        $invoiceStockSales = $invoiceStockSales
            ->join('stock_sales', 'stock_sales.invoice_id = invoice_stock_sales.id')
            ->findAll();

        $stockLogStockSales = [];
        foreach ($invoiceStockSales as $key => $value) {
            $stockLogStockSales[] = [
                'description' => "Penjualan $value[invoice_no]",
                'product_id' => $value['product_id'],
                'datetime' => $value['date'],
                'stock_in' => 0,
                'stock_out' => $value['qty'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['price'],
            ];
        }

        // insert batch per 150 data
        $stockLog->insertBatch($stockLogStockSales, null, 150);

        // Stock Spoil
        $spoil = $spoil
            ->select('stock_spoil.*, products.selling_price')
            ->join('products', 'products.id = stock_spoil.product_id')
            ->findAll();

        $stockLogSpoil = [];
        foreach ($spoil as $key => $value) {
            $stockLogSpoil[] = [
                'description' => "Stok terbuang ({$value['note']})",
                'product_id' => $value['product_id'],
                'datetime' => $value['created_at'],
                'stock_in' => 0,
                'stock_out' => $value['qty'],
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        return 'success';
    }

    public function init_stock_log_v2()
    {
        $stockLog = new StockLogModel();
        $product = new ProductsModel();

        $stockLog->truncate();

        //tambah stok manual
        $getProduct = $product->findAll();

        $stockLogStockManual = [];
        foreach ($getProduct as $key => $value) {
            $stockLogStockManual[] = [
                'description' => "Stok Awal",
                'product_id' => $value['id'],
                'datetime' => "2022-01-01 00:00:01",
                'stock_in' => $value['stock'],
                'stock_out' => 0,
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        $stockLog->insertBatch($stockLogStockManual);

        return 'success';
    }

    public function init_stock_log_v3()
    {
        $stockLog = new StockLogModel();
        $product = new ProductsModel();

        $stockLog->truncate();

        //tambah stok manual
        $getProduct = $product->findAll();

        $stockLogStockManual = [];
        foreach ($getProduct as $key => $value) {
            $stockLogStockManual[] = [
                'description' => "Stok Awal",
                'product_id' => $value['id'],
                'datetime' => "2023-01-01 00:00:01",
                'stock_in' => $value['stock'],
                'stock_out' => 0,
                'cogs' => $value['cogs'],
                'selling_price' => $value['selling_price'],
            ];
        }

        $stockLog->insertBatch($stockLogStockManual);

        return 'success';
    }

    public function init_stock_log_v4()
    {
        $stockLog = new StockLogModel();

        //tambah stok manual
        $getLogs = $stockLog->where("description", "Stok Awal")->findAll();

        $stockLogStockUpdate = [];
        foreach ($getLogs as $key => $value) {
            $stockLogStockUpdate[] = [
                'id' => $value['id'],
                'datetime' => "2022-01-01 00:00:01"
            ];
        }

        $stockLog->updateBatch($stockLogStockUpdate, 'id');

        return 'success';
    }

    public function fix_stock_log_out()
    {
        ini_set('memory_limit', '10G');

        $stockLog = new StockLogModel();
        $invoiceStockSales = new InvoiceStockSalesModel();
        $spoil = new StockSpoilModel();

        $sqlContent = "";

        // delete log out
        $stockLog->where('stock_in', 0)->delete();

        // delete log out hapus penjualan
        $stockLog->like('description', 'Hapus Penjualan', 'both')->delete();

        //Penjualan Stok
        $invoiceStockSales = $invoiceStockSales
            ->join('stock_sales', 'stock_sales.invoice_id = invoice_stock_sales.id')
            ->findAll();

        $sqlContent .= "-- INSERT STOCK SALES" . PHP_EOL;
        $row = 0;
        $sqlInsertValue = "";
        foreach ($invoiceStockSales as $key => $value) {
            $row++;

            $sqlInsertValue .= PHP_EOL . "(" .
                "'Penjualan $value[invoice_no]'," .
                "'$value[product_id]'," .
                "'$value[date]'," .
                "0," .
                "$value[qty]," .
                "0," .
                "0," .
                "'$value[created_at]'" .
                ")";

            switch ($row) {
                case 150:
                    $row = 0;
                    $sqlContent .= PHP_EOL . "INSERT INTO stock_logs (description, product_id, datetime, stock_in, stock_out, cogs, selling_price, created_at) VALUES $sqlInsertValue;";
                    $sqlInsertValue = "";
                    break;
                default:
                    $sqlInsertValue .= ",";
                    break;
            }
        }

        $sqlContent .= PHP_EOL . PHP_EOL . "-- INSERT STOCK SPOIL" . PHP_EOL;

        // Stock Spoil
        $spoil = $spoil
            ->select('stock_spoil.*, products.selling_price')
            ->join('products', 'products.id = stock_spoil.product_id')
            ->findAll();

        $row = 0;
        $sqlInsertValue = "";
        foreach ($spoil as $key => $value) {
            $row++;

            $sqlInsertValue .= PHP_EOL . "(" .
                "'Stok terbuang ({$value['note']})'," .
                "'$value[product_id]'," .
                "'$value[date]'," .
                "0," .
                "$value[qty]," .
                "0," .
                "0," .
                "'$value[date]'" .
                ")";

            switch ($row) {
                case 150:
                    $row = 0;
                    $sqlContent .= PHP_EOL . "INSERT INTO stock_logs (description, product_id, datetime, stock_in, stock_out, cogs, selling_price, created_at) VALUES $sqlInsertValue;";
                    $sqlInsertValue = "";
                    break;
                default:
                    $sqlInsertValue .= ",";
                    break;
            }
        }

        $filename = "database_dump.sql";

        // Set the headers to prompt the browser to download the file
        header('Content-Type: application/sql');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . strlen($sqlContent));

        echo $sqlContent;
    }

    public function reset_stock_log()
    {
        $stockLog = new StockLogModel();
        $product = new ProductsModel();

        $stockLog->truncate();

        $p = $product->where('stock !=', 0)->findAll();

        $stock = [];
        foreach ($p as $pi) {
            $stock[] = [
                'description' => "Stok Awal",
                'product_id' => $pi['id'],
                'datetime' => date('Y-m-d H:i:s'),
                'stock_in' => $pi['stock'],
                'stock_out' => 0,
                'cogs' => 0,
                'selling_price' => 0,
            ];
        }

        $stockLog->insertBatch($stock, null, 150);

        return 'success';
    }

    public function test_date()
    {
        echo date("Y-m-d H:i:s", strtotime("23/09/2022"));
    }
}
