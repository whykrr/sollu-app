<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancialExpenseModel extends FinancialModel
{
    /**
     * datatable custom select
     */
    public function datatablesCustom($args)
    {
        $custom = $this->select('financials.*, financial_accounts.name as acc_name')
            ->join('financial_accounts', 'financial_accounts.id = financials.account_id')
            ->where('financials.type', 'out');

        return $custom;
    }
}
