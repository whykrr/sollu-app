<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductsModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'products';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'code',
        'barcode',
        'name',
        'category_id',
        'unit_id',
        'stock_min',
        'cogs',
        'selling_price',
        'description',
        'photo',
        'barcode_file',
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'code' => [
            'label' => 'Kode',
            'rules' => 'is_unique[products.code, id, {id}]',
        ],
        'name' => [
            'label' => 'Nama',
            'rules' => 'required',
        ],
        'unit_id' => [
            'label' => 'Satuan',
            'rules' => 'required',
        ],
        'stock_min' => [
            'label' => 'Minimal Stok',
            'rules' => 'required',
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
        'code' => [
            'is_unique' => '{field} telah digunakan.',
        ],
        'name' => [
            'required' => '{field} harus diisi.',
        ],
        'unit_id' => [
            'required' => '{field} harus diisi.',
        ],
        'stock_min' => [
            'required' => '{field} harus diisi.',
        ],
        'cogs' => [
            'required' => '{field} harus diisi.',
        ],
        'selling_price' => [
            'required' => '{field} harus diisi.',
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = ["genrateCode"];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeFind           = [];
    protected $afterFind            = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];

    public function genrateCode($data)
    {
        //check code if not set
        if ($data['data']['code'] == "") {
            $data['data']['code'] = "P" . date("ymd") . "-" . date("his");
        }
        return $data;
    }

    /**
     * datatables custom select
     */
    public function datatablesCustom(array $args)
    {
        $custom = $this->select('products.*, units.name as unit_name, pc.name as category_name')
            ->join('product_categories pc', 'pc.id = products.category_id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->orderBy('products.created_at', 'desc');

        return $custom;
    }

    /**
     * get detail
     * 
     * @param int $id
     */
    public function findDetail($id)
    {
        $detail = $this->select('products.*, units.name as unit_name, pc.name as category_name')
            ->join('product_categories pc', 'pc.id = products.category_id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->where('products.id', $id)
            ->first();

        return $detail;
    }

    /**
     * autocomplete product
     */
    public function autocomplete($keyword)
    {
        $data = $this
            ->select('products.*, units.name as unit_name')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->like('products.name', $keyword, 'both')
            ->orLike('products.code', $keyword, 'both')
            ->orLike('products.barcode', $keyword)
            ->findAll();

        $remapData = [];
        foreach ($data as $row) {
            $remapData[] = [
                'id' => $row['id'],
                'name' => $row['code'] . ' - ' . $row['name'] . ' - ' . $row['barcode'],
                'cogs' => (int) $row['cogs'],
                'selling_price' => (int) $row['selling_price'],
                'unit' => $row['unit_name'],
            ];
        }

        return $remapData;
    }

    /**
     * update stock with update batch
     * 
     * @param array $data
     * return boolean 
     */
    public function updateStocks(array $data)
    {
        $builder = $this->db->table($this->table);
        $builder->updateBatch($data, 'id');

        return true;
    }
}
