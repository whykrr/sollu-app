<?php

namespace App\Controllers\Debtcredit;

use App\Controllers\BaseController;
use App\Models\AccountReceivableModel;
use App\Models\AccountReceivableDetailModel;
use App\Models\FinancialModel;

class Credit extends BaseController
{
    /**
     * show page list
     */
    public function index()
    {
        $data['sidebar_active'] = 'debtcredit-credit';
        return view('debtcredit/credit/list', $data);
    }

    /**
     * show page form
     */
    public function pay($id)
    {
        $data = [];
        $account_receivable = new AccountReceivableModel();
        $receive = $account_receivable->getDetails($id);

        $data['data'] = $receive['data'];
        return view('debtcredit/credit/_pay', $data);
    }

    /**
     * attemp pay
     */
    public function attemppay()
    {
        $account_receivableDetail = new AccountReceivableDetailModel();
        $account_receivable = new AccountReceivableModel();
        $financial = new FinancialModel();

        $data = $this->request->getPost();

        // check ammount > remaining_credit
        if ($data['amount'] > $data['remaining_credit']) {
            $json = [
                "message" => "validation error",
                "validation_error" => [
                    "amount" => "jumlah melebihi dari sisa piutang"
                ],
            ];

            return $this->respond($json, 400);
        }

        $insert['account_receivable_id'] = $data['account_receivable_id'];
        $insert['date'] = $data['date'];
        $insert['amount'] = $data['amount'];
        $insert['user_id'] = user_id();

        $update['id'] = $data['account_receivable_id'];
        $update['pay_amount'] = $data['pay_amount'] + $data['amount'];

        // check pay ammount if same with total credit so change status to '1'
        if ($update['pay_amount'] == $data['total_credit']) {
            $update['status'] = 1;
        }

        $financialData = [
            'account_id' => '11-2',
            'reference_id' => $data['account_receivable_id'],
            'type' => 'in',
            'amount' => $data['amount'],
            'description' => 'Pembayaran piutang',
            'user_id' => user_id(),
        ];


        // instace db
        $db = \Config\Database::connect();

        // transaction start
        $db->transStart();

        $result = $account_receivableDetail->save($insert);

        // save data
        if (!$result) {
            $db->transRollback();
            // get validation error message
            $errors = $account_receivableDetail->errors();
            $json = [
                "message" => "validation error",
                "validation_error" => $errors,
            ];

            return $this->respond($json, 400);
        }

        // save receivable
        if (!$account_receivable->save($update)) {
            $db->transRollback();
            // get validation error message
            $errors = $account_receivable->errors();
            $json = [
                "message" => implode(', ', $errors),
            ];

            return $this->respond($json, 500);
        }

        // save financial
        if (!$financial->save($financialData)) {
            $db->transRollback();
            // get validation error message
            $errors = $financial->errors();
            $json = [
                "message" => implode(', ', $errors),
            ];

            return $this->respond($json, 500);
        }

        // transaction commit
        $db->transCommit();

        $json = [
            "message" => 'Data berhasil disimpan',
        ];

        // redirect to list
        return $this->respond($json, 200);
        return $result;
    }


    /**
     * show page detail
     */
    public function detail($id)
    {
        // instace models
        $account_receivable = new AccountReceivableModel();

        $data = $account_receivable->getDetails($id);
        return view('debtcredit/credit/_detail', $data);
    }
}
