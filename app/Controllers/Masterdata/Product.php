<?php

namespace App\Controllers\Masterdata;

use App\Models\UnitsModel;
use App\Controllers\Export;
use App\Models\ProductsModel;
use App\Controllers\BaseController;
use App\Models\ProductCategoriesModel;

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

    public function print()
    {
        $query = $this->request->getGet();

        $products = new ProductsModel();

        // find product
        $product = $products->find($query['id']);

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $data['barcode'] =  '<img width="100%" src="data:image/png;base64,' . base64_encode($generator->getBarcode($product['barcode'], $generator::TYPE_CODE_128)) . '">';
        $data['loop'] = $query['loop'];
        $data['name'] = $product['name'];
        $data['code'] = $product['barcode'];
        return view('masterdata/product/_print', $data);
    }

    /**
     * create function to export excel
     */
    public function export()
    {
        $model = new ProductsModel();
        $data = $model->findAllDetail();
        $filename = 'data-product';

        $format = [
            ['label' => 'No', 'data' => 'increament'],
            ['label' => 'Kode', 'data' => 'code'],
            ['label' => 'Barcode', 'data' => 'barcode'],
            ['label' => 'Nama', 'data' => 'name'],
            ['label' => 'Kategori', 'data' => 'category_name'],
            ['label' => 'Satuan', 'data' => 'unit_name'],
            ['label' => 'Harga', 'data' => 'cogs'],
            ['label' => 'Harga', 'data' => 'selling_price'],
            ['label' => 'Keterangan', 'data' => 'description'],
        ];

        return Export::do($format, $data, $filename);
    }

    public function migrate_product($category_id)
    {
        $filename = $get = $this->request->getGet('file');

        // call method _read_excel
        $data = $this->_read_excel($filename);
        $unit = [];
        $units = [];
        dd($data);


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
                'category_id' => $category_id,
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
