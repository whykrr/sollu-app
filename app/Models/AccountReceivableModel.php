<?php

namespace App\Models;

use App\Database\Migrations\AccountReceivableDetail;
use CodeIgniter\Model;
use App\Models\StockSalesModel;
use App\Models\AccountReceivableDetailModel;

class AccountReceivableModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'account_receivable';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = true;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'date',
        'invoice_id',
        'customer',
        'amount',
        'pay_amount',
        'description',
        'due_date',
        'status',
        'user_id',
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeFind           = [];
    protected $afterFind            = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];

    /**
     * datatables custom select
     */
    public function datatablesCustom(array $args)
    {
        $this->select('account_receivable.*, inv.invoice_no as invoice_no');
        $this->join('invoice_stock_sales inv', 'inv.id = account_receivable.invoice_id');
        $this->orderBy('account_receivable.id', 'desc');

        return $this;
    }

    /**
     * get details
     */
    public function getDetails($id)
    {
        // instace model
        $sales = new StockSalesModel();
        $detail = new AccountReceivableDetailModel();

        $this->select('account_receivable.*, inv.invoice_no as invoice_no, inv.date as invoice_date');
        $this->join('invoice_stock_sales inv', 'inv.id = account_receivable.invoice_id');
        $this->where('account_receivable.id', $id);

        $r['data'] = $this->get()->getRowArray();
        $r['detail'] = $detail->where('account_receivable_id', $id)->findAll();
        $r['items'] = $sales
            ->select('stock_sales.*, products.name as product_name, products.code as product_code, units.name as unit_name')
            ->join('products', 'products.id = stock_sales.product_id')
            ->join('units', 'units.id = products.unit_id')
            ->where('invoice_id', $r['data']['invoice_id'])->findAll();

        return $r;
    }
}
