<?php

namespace App\Models;

use CodeIgniter\Model;

class StockPurchaseModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'stock_purchases';
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
        'cogs',
        'selling_price',
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
            'label' => 'Produk',
            'rules' => 'required|integer',
        ],
        'qty' => [
            'label' => 'Jumlah',
            'rules' => 'required|integer|greater_than[0]',
        ],
        'cogs' => [
            'label' => 'Harga Pokok',
            'rules' => 'required',
        ],
        'selling_price' => [
            'label' => 'Harga Jual',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'product_id' => [
            'required' => '{field} harus diisi',
            'integer' => '{field} harus berupa angka',
        ],
        'qty' => [
            'required' => '{field} harus diisi',
            'integer' => '{field} harus berupa angka',
            'greater_than' => '{field} harus lebih besar dari 0',
        ],
        'cogs' => [
            'required' => '{field} harus diisi',
        ],
        'selling_price' => [
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
     * get by invoice_id
     * 
     * @param int $invoice_id
     * @return array
     */
    public function getByInvoice($invoice_id)
    {
        $builder = $this->db->table($this->table . ' as sp');
        $builder->select('sp.*, products.code as product_code, products.name as product_name, units.name as unit_name');
        $builder->join('products', 'products.id = sp.product_id');
        $builder->join('units', 'units.id = products.unit_id');
        $builder->where('sp.invoice_id', $invoice_id);
        $builder->orderBy('sp.id', 'ASC');
        $query = $builder->get();
        return $query->getResultArray();
    }
}
