<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceStockSalesMoveModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'invoice_stock_sales';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [];

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

    public function datatablesCustom(array $args)
    {
        $type =  @$args['type_filter'] ?: 'monthly';

        $where['month'] = @$args['month'] ?: date('m');
        $where['year'] = @$args['year'] ?: date('Y');
        $where['start_date'] = @$args['start_date'] ?: date('Y-m-d');
        $where['end_date'] = @$args['end_date'] ?: date('Y-m-d');
        $where['date'] = @$args['date'] ?: date('Y-m-d');

        $custom = $this->select('id, date, invoice_no, note, grand_total');
        $custom->where('type', 'move');
        if ($type == 'monthly') {
            $custom->where('MONTH(date)', $where['month']);
            $custom->where('YEAR(date)', $where['year']);
        } elseif ($type == 'daily') {
            $custom->where('date', $where['date']);
        } else if ($type == 'range') {
            $custom->where('date >=', $where['start_date']);
            $custom->where('date <=', $where['end_date']);
        }

        if (@$args['customer'] != "") {
            $custom->where('customer_id', $args['customer']);
        }

        $custom->orderBy('date', 'desc');
        $custom->orderBy('created_at', 'desc');

        return $custom;
    }
}
