<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceStockPurchaseModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'invoice_stock_purchases';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'id',
        'invoice_no',
        'supplier_id',
        'supplier',
        'date',
        'total',
        'discount',
        'grand_total',
        'payment_type',
        'note',
        'user_id',
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'invoice_no' => [
            'label' => 'No. Invoice',
            'rules' => 'required|alpha_numeric_punct|max_length[255]',
        ],
        'date' => [
            'label' => 'Tanggal',
            'rules' => 'required',
        ],
        'payment_type' => [
            'label' => 'Tipe Pembayaran',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'invoice_no' => [
            'required' => '{field} harus diisi',
            'alpha_numeric_punct' => '{field} hanya boleh berisi huruf, angka, spasi, titik dan garis bawah',
            'max_length' => '{field} maksimal 255 karakter',
        ],
        'date' => [
            'required' => '{field} harus diisi',
        ],
        'payment_type' => [
            'required' => '{field} harus diisi',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = ["setUserID"];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeFind           = [];
    protected $afterFind            = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];

    /**
     * Set User ID
     */
    public function setUserID($data)
    {
        $data['data']['user_id'] = user_id();
        return $data;
    }

    public function datatablesCustom(array $args)
    {
        $type =  @$args['type_filter'] ?: 'monthly';

        $where['month'] = @$args['month'] ?: date('m');
        $where['year'] = @$args['year'] ?: date('Y');
        $where['start_date'] = @$args['start_date'] ?: date('Y-m-d');
        $where['end_date'] = @$args['end_date'] ?: date('Y-m-d');
        $where['date'] = @$args['date'] ?: date('Y-m-d');

        $custom = $this->select('id, date, invoice_no, supplier, grand_total');
        if ($type == 'monthly') {
            $custom->where('MONTH(date)', $where['month']);
            $custom->where('YEAR(date)', $where['year']);
        } elseif ($type == 'daily') {
            $custom->where('date', $where['date']);
        } else if ($type == 'range') {
            $custom->where('date >=', $where['start_date']);
            $custom->where('date <=', $where['end_date']);
        }

        if (@$args['supplier'] != "") {
            $custom->where('supplier_id', $args['supplier']);
        }

        $custom->orderBy('created_at', 'desc');

        return $custom;
    }

    /**
     * get available year
     */
    public function getAvailableYear()
    {
        $builder = $this->db->table($this->table);
        $builder->select('DISTINCT YEAR(created_at) as year');
        $builder->orderBy('year', 'DESC');
        $result = $builder->get()->getResultArray();

        return $result;
    }
}
