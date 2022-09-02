<?php

namespace App\Models;

use CodeIgniter\Model;

class StockSpoilModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'stock_spoil';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'date',
        'product_id',
        'cogs',
        'qty',
        'total',
        'note',
        'user_id',
    ];

    // Dates
    protected $useTimestamps        = false;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'product_id' => [
            'label' => 'Produk',
            'rules' => 'required',
        ],
        'qty' => [
            'label' => 'Jumlah',
            'rules' => 'required|numeric',
        ],
    ];
    protected $validationMessages   = [
        'product_id' => [
            'required' => '{field} harus diisi',
        ],
        'qty' => [
            'required' => '{field} harus diisi',
            'numeric' => '{field} harus berupa angka',
        ],
    ];
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

        $custom = $this->select('stock_spoil.id, stock_spoil.date, products.name as product_name, qty, units.name as unit_name, stock_spoil.note')
            ->join('products', 'products.id = stock_spoil.product_id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left');

        $where['month'] = @$args['month'] ?: date('m');
        $where['year'] = @$args['year'] ?: date('Y');
        $where['start_date'] = @$args['start_date'] ?: date('Y-m-d');
        $where['end_date'] = @$args['end_date'] ?: date('Y-m-d');
        $where['date'] = @$args['date'] ?: date('Y-m-d');

        if ($type == 'monthly') {
            $custom->where('MONTH(date)', $where['month']);
            $custom->where('YEAR(date)', $where['year']);
        } elseif ($type == 'daily') {
            $custom->where('date', $where['date']);
        } else if ($type == 'range') {
            $custom->where('date >=', $where['start_date']);
            $custom->where('date <=', $where['end_date']);
        }

        $custom->orderBy('id', 'desc');

        return $custom;
    }
}
