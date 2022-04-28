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
            ->join('units', 'units.id = products.unit_id', 'left');

        if ($args['search'] != "") {
            // group like
            $custom->groupStart();
            $custom->like('products.name', $args['search'], 'both')
                ->orLike('products.code', $args['search'], 'both')
                ->orLike('products.barcode', $args['search'], 'both');
            $custom->groupEnd();
        }
        if ($args['category_id'] != "") {
            $custom->where('products.category_id', $args['category_id']);
        }

        $custom->orderBy('products.code', 'asc');

        return $custom;
    }

    /**
     * find all detail products
     */
    public function findAllDetail()
    {
        $custom = $this->select('products.*, units.name as unit_name, pc.name as category_name')
            ->join('product_categories pc', 'pc.id = products.category_id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->orderBy('products.code', 'asc')
            ->get()->getResultArray();

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
     * scan barcode product
     */
    public function barcode($barcode)
    {
        $data = $this
            ->select('products.*, units.name as unit_name')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->where('products.barcode', $barcode)
            ->first();

        // check
        if (!$data) {
            return null;
        }

        $remapData = [
            'id' => $data['id'],
            'name' => $data['code'] . ' - ' . $data['name'],
            'cogs' => (int) $data['cogs'],
            'selling_price' => (int) $data['selling_price'],
            'unit' => $data['unit_name'],
        ];

        return $remapData;
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
            ->findAll();

        $remapData = [];
        foreach ($data as $row) {
            $remapData[] = [
                'id' => $row['id'],
                'name' => $row['code'] . ' - ' . $row['name'],
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
