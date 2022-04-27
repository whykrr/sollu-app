<?php

namespace App\Controllers\Masterdata;

use App\Controllers\BaseController;
use App\Models\ProductCategoriesModel;
use Cocur\Slugify\Slugify;

class Product_Category extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'master-data-prodcategory';
        return view('masterdata/product_category/list', $data);
    }

    /**
     * show form 
     */
    public function form($id = null)
    {
        // get model
        $model = new ProductCategoriesModel();
        $data = [];

        // check id if not null
        if ($id != null) {
            $data['edit'] = $model->find($id);
        }
        return view('masterdata/product_category/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new ProductCategoriesModel();

        // get data
        $data = $this->request->getPost();

        $data['slug'] = (new Slugify())->slugify($data['name']);

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
        $model = new ProductCategoriesModel();

        // delete data
        if (!$model->delete($id)) {
            $json = [
                "message" => "Data gagal dihapus",
            ];

            return $this->respond($json, 400);
        }

        $json = [
            "message" => 'Data berhasil dihapus',
        ];

        // redirect to list
        return $this->respond($json, 200);
    }
}
