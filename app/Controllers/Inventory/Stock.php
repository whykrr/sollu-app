<?php

namespace App\Controllers\Inventory;

use App\Controllers\BaseController;
use App\Models\InvoiceStockPurchaseModel;
use App\Models\StockPurchaseModel;
use App\Models\StockModel;
use App\Models\ProductsModel;
use App\Models\FinancialModel;
use App\Models\ProductCategoriesModel;

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

        $name = 'Laporan Stok ' . formatDateID(date('Y-m-d'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Data Stok');

        $worksheet = $spreadsheet->setActiveSheetIndex(0);
        $worksheet->setCellValue('A1', 'No')->getStyle('A1')->getFont()->setBold(true);
        $worksheet->setCellValue('B1', 'Kode')->getStyle('B1')->getFont()->setBold(true);
        $worksheet->setCellValue('C1', 'Barcode')->getStyle('C1')->getFont()->setBold(true);
        $worksheet->setCellValue('D1', 'Nama')->getStyle('D1')->getFont()->setBold(true);
        $worksheet->setCellValue('E1', 'Kategori')->getStyle('E1')->getFont()->setBold(true);
        $worksheet->setCellValue('F1', 'Satuan')->getStyle('F1')->getFont()->setBold(true);
        $worksheet->setCellValue('G1', 'Stok')->getStyle('G1')->getFont()->setBold(true);
        $worksheet->setCellValue('H1', 'Keterangan')->getStyle('H1')->getFont()->setBold(true);

        // $row_start = 2;
        foreach ($data as $key => $value) {
            $worksheet->setCellValue('A' . ($key + 2), $key + 1);
            $worksheet->setCellValue('B' . ($key + 2), $value['code']);
            $worksheet->setCellValue('C' . ($key + 2), $value['barcode']);
            $worksheet->setCellValue('D' . ($key + 2), $value['name']);
            $worksheet->setCellValue('E' . ($key + 2), $value['category_name']);
            $worksheet->setCellValue('F' . ($key + 2), $value['unit_name']);
            $worksheet->setCellValue('G' . ($key + 2), $value['stock']);
            $worksheet->setCellValue('H' . ($key + 2), $value['description']);
        }

        // resize column
        foreach (range('A', 'H') as $columnID) {
            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$name.xlsx");

        return redirect()->to("$name.xlsx");
    }
}
