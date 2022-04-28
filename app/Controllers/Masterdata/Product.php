<?php

namespace App\Controllers\Masterdata;

use App\Controllers\BaseController;
use App\Models\ProductsModel;
use App\Models\ProductCategoriesModel;
use App\Models\UnitsModel;

class Product extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $category = new ProductCategoriesModel();


        $data['sidebar_active'] = 'master-data-produk';
        $data['category'] = $category->findAll();

        return view('masterdata/product/list', $data);
    }

    /**
     * show form 
     */
    public function form($id = null)
    {
        // get model
        $model = new ProductsModel();
        $data = [];

        // instance category 
        $category = new ProductCategoriesModel();

        // instance unit
        $unit = new UnitsModel();

        // get data category
        $data['category'] = $category->findAll();

        // get data unit
        $data['unit'] = $unit->findAll();

        // check id if not null
        if ($id != null) {
            $data['edit'] = $model->find($id);
        }
        return view('masterdata/product/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new ProductsModel();


        // get data
        $data = $this->request->getPost();

        // save data
        if (!$model->save($data)) {
            // get validation error message
            $errors = $model->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        // respond success
        $json = [
            "message" => 'Data berhasil disimpan',
        ];
        return $this->respond($json, 200);
    }

    /**
     * delete data
     */
    public function delete($id)
    {
        // get model
        $model = new ProductsModel();

        // delete data
        $model->delete($id);

        // respond success
        $json = [
            "message" => 'Data berhasil dihapus',
        ];
        return $this->respond($json, 200);
    }

    /**
     * function get_product for autocomplete
     */
    public function autocomplete()
    {
        $product = new ProductsModel();
        $key = $this->request->getGet('q');
        if ($key == null) {
            $key = "";
        }

        $json = $product->autocomplete($key);
        return $this->response->setJSON($json);
    }

    /**
     * function get_product for autocomplete
     */
    public function barcode()
    {
        $product = new ProductsModel();
        $key = $this->request->getGet('scan');
        if ($key == null) {
            return $this->respond([], 400);
        }

        $json = $product->barcode($key);
        if ($json == null) {
            return $this->respond([], 400);
        }

        return $this->respond($json, 200);
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $model = new ProductsModel();
        $data = $model->findAllDetail();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Data Produk');

        $worksheet = $spreadsheet->setActiveSheetIndex(0);
        $worksheet->setCellValue('A1', 'No');
        $worksheet->setCellValue('B1', 'Kode');
        $worksheet->setCellValue('C1', 'Barcode');
        $worksheet->setCellValue('D1', 'Nama');
        $worksheet->setCellValue('E1', 'Kategori');
        $worksheet->setCellValue('F1', 'Satuan');
        $worksheet->setCellValue('G1', 'Harga Beli');
        $worksheet->setCellValue('H1', 'Harga Jual');
        $worksheet->setCellValue('I1', 'Keterangan');

        // $row_start = 2;
        foreach ($data as $key => $value) {
            $worksheet->setCellValue('A' . ($key + 2), $key + 1);
            $worksheet->setCellValue('B' . ($key + 2), $value['code']);
            $worksheet->setCellValue('C' . ($key + 2), $value['barcode']);
            $worksheet->setCellValue('D' . ($key + 2), $value['name']);
            $worksheet->setCellValue('E' . ($key + 2), $value['category_name']);
            $worksheet->setCellValue('F' . ($key + 2), $value['unit_name']);
            $worksheet->setCellValue('G' . ($key + 2), $value['cogs']);
            $worksheet->setCellValue('H' . ($key + 2), $value['selling_price']);
            $worksheet->setCellValue('I' . ($key + 2), $value['description']);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('data-product.xlsx');

        return redirect()->to('data-product.xlsx');
    }

    public function migrate_product()
    {
        $filename = $get = $this->request->getGet('file');

        // call method _read_excel
        $data = $this->_read_excel($filename);
        $unit = [];
        $units = [];

        foreach ($data as $key => $product) {
            if ($key == 0) {
                continue;
            }

            if (!in_array($product[7], $unit)) {
                array_push($unit, $product[7]);
                array_push($units, [
                    'name' =>  $product[7]
                ]);
            }
        }

        $unitM = new UnitsModel();
        $productM = new ProductsModel();

        $unitM->insertBatch($units);


        $units = $unitM->findAll();

        $unit_names = array_column($units, 'name');
        $unit_ids = array_column($units, 'id');

        $products = [];

        foreach ($data as $key => $p) {
            if ($key == 0) {
                continue;
            }

            $product = [
                'code' => $p[0],
                'barcode' => $p[1],
                'name' => $p[2],
                'category_id' => 0,
                'stock' => 0,
                'stock_min' => 0,
                'cogs' => $p[8],
                'selling_price' => $p[9],
                'description' => (empty($p[10])) ? '' : $p[10],
            ];

            $unit_id = $unit_ids[array_search($p[7], $unit_names)];

            $product['unit_id'] = $unit_id;

            array_push($products, $product);
            $productM->save($product);
        }
        // print_r($productM->errors());
        dd($products);
        // $productM->insertBatch($products);
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
