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
        $data['sidebar_active'] = 'master-data-produk';
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
}
