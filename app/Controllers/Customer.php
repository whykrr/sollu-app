<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;

class Customer extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'customer';
        return view('customer/list', $data);
    }

    /**
     * autocomplete
     */
    public function autocomplete()
    {
        $customer = new CustomerModel();

        $keyword = $this->request->getGet('q');
        $data = $customer->like('name', $keyword)->findAll(10);

        $json = [];
        foreach ($data as $value) {
            $json[] = [
                'id' => $value['id'],
                'name' => $value['name']
            ];
        }

        return $this->response->setJSON($json);
    }

    /**
     * show form 
     */
    public function form($id = null)
    {
        // get model
        $model = new CustomerModel();
        $data = [];

        // check id if not null
        if ($id != null) {
            $data['edit'] = $model->find($id);
        }
        return view('customer/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new CustomerModel();

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
        $model = new CustomerModel();

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
