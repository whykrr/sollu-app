<?php

namespace App\Models;

use CodeIgniter\Model;

class StockSalesModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'stock_sales';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'invoice_id',
        'product_id',
        'qty',
        'price',
        'sub_total'
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'product_id' => [
            'label' => 'Supplier',
            'rules' => 'required',
        ],
        'qty' => [
            'label' => 'Tanggal',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'product_id' => [
            'required' => '{field} harus diisi',
        ],
        'qty' => [
            'required' => '{field} harus diisi',
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

    /**
     * get data product sales
     * 
     * @param int $id
     * @return array
     */
    public function getListSales($invoice_id)
    {
        $builder = $this->db->table($this->table);
        $builder->select('stock_sales.*, products.code as product_code, products.name as product_name');
        $builder->join('products', 'products.id = stock_sales.product_id', 'left');
        $builder->where('stock_sales.invoice_id', $invoice_id);
        $result = $builder->get()->getResultArray();

        return $result;
    }
}
