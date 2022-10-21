<?php

namespace App\Controllers\Finance;

use App\Controllers\BaseController;
use App\Models\FinancialModel;
use App\Models\FinancialAccountModel;

class Expense extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'finance-expense';
        return view('finance/expense/list', $data);
    }

    /**
     * show page detail
     */
    public function detail($id)
    {
        // get instance model
        $financialModel = new FinancialModel();

        $data['data'] = $financialModel->getDetail($id);
        return view('finance/expense/_detail', $data);
    }

    /**
     * show page add
     */
    public function form($id = null)
    {
        $data = [];
        $expense = new FinancialModel();

        if ($id != null) {
            $data['edit'] = $expense->find($id);
        }

        return view('finance/expense/_form', $data);
    }

    /**
     * save data
     */
    public function save()
    {
        // get data
        $data = $this->request->getPost();

        // save data
        $expense = new FinancialModel();
        $data['type'] = 'out';
        $data['user_id'] = user_id();
        if (!$expense->save($data)) {
            // get validation error message
            $errors = $expense->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        log_event("Input Pengeluaran", $data);

        $json = [
            "message" => 'Data berhasil disimpan',
        ];

        // success respond
        return $this->respond($json, 200);
    }

    /**
     * delete data
     */
    public function delete($id)
    {
        // delete data
        $expense = new FinancialModel();
        $data = $expense->find($id);
        $expense->delete($id);

        log_event("Hapus Pengeluaran", $data);

        $json = [
            "message" => 'Data berhasil dihapus',
        ];
        return $this->respond($json, 200);
    }
}
