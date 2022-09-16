<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceStockSalesModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'invoice_stock_sales';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'id',
        'invoice_no',
        'type',
        'cashier_log_id',
        'customer_id',
        'customer',
        'date',
        'total',
        'discount',
        'grand_total',
        'payment_type',
        'pay',
        'return',
        'note',
        'user_id',
        'created_at',
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
            'rules' => 'alpha_numeric_punct|max_length[255]',
        ],
        'payment_type' => [
            'label' => 'Tipe Pembayaran',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'invoice_no' => [
            'alpha_numeric_punct' => '{field} hanya boleh berisi huruf, angka, spasi, titik dan garis bawah',
            'max_length' => '{field} maksimal 255 karakter',
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

        $custom = $this->select('id, date, invoice_no, customer, grand_total');
        $custom->where('type', 'sales');
        if ($type == 'monthly') {
            $custom->where('MONTH(date)', $where['month']);
            $custom->where('YEAR(date)', $where['year']);
        } elseif ($type == 'daily') {
            $custom->where('DATE(date)', $where['date']);
        } else if ($type == 'range') {
            $custom->where('DATE(date) >=', $where['start_date']);
            $custom->where('DATE(date) <=', $where['end_date']);
        }

        if (@$args['customer'] != "") {
            $custom->where('customer_id', $args['customer']);
        }
        $custom->orderBy('date', 'desc');
        $custom->orderBy('created_at', 'desc');

        return $custom;
    }

    /**
     * get detail invoice
     */
    public function getDetail($invoice_id)
    {
        $query = $this->db->table($this->table)
            ->select('invoice_stock_sales.*, users.name as user_name')
            ->join('users', 'users.id = invoice_stock_sales.user_id', 'left')
            ->where('invoice_stock_sales.id', $invoice_id)
            ->get();
        return $query->getRowArray();
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

    /**
     * get sales data
     * 
     * @param string $type
     * @param mixed $date
     */
    public function getSales($type, $date)
    {
        // if $type is 'daily'
        if ($type == 'daily') {
            $data = $this->select('COUNT(id) AS total')
                ->where('type', 'sales')
                ->where('date', $date)
                ->first();
        }
        return $data['total'];
    }

    /**
     * get income data
     * 
     * @param string $type
     * @param mixed $date
     */
    public function getIncome($type, $date)
    {
        // if $type is 'daily'
        if ($type == 'daily') {
            $data = $this->select('SUM(grand_total) AS total')
                ->where('type', 'sales')
                ->where('date', $date)
                ->first();

            $r = $data['total'];
        }

        // if $type is 'monthly'
        if ($type == 'monthlyAll') {
            $data = $this->select('SUM(grand_total) AS total, date')
                ->where('type', 'sales')
                ->like('date', $date, 'after')
                ->groupBy('date')
                ->findAll();

            $r['labels'] = array_column($data, 'date');

            // reformat date
            foreach ($r['labels'] as $key => $value) {
                $r['labels'][$key] = formatDateSimple($value);
            }

            $r['data'] = array_column($data, 'total');
        }



        return $r;
    }
}
