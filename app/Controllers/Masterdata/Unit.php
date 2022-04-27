<?php

namespace App\Controllers\Masterdata;

use App\Controllers\BaseController;
use App\Models\UnitsModel;

class Unit extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'master-data-unit';
        return view('masterdata/unit/list', $data);
    }

    /**
     * show form 
     */
    public function form($id = null)
    {
        // get model
        $model = new UnitsModel();
        $data = [];

        // check id if not null
        if ($id != null) {
            $data['edit'] = $model->find($id);
        }
        return view('masterdata/unit/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get model
        $model = new UnitsModel();

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
        $model = new UnitsModel();

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
