<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'suppliers';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'name',
        'address',
        'phone',
    ];

    // Dates
    protected $useTimestamps        = false;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name' => [
            'label' => 'Nama Supplier',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'name' => [
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

    // add supplier if id is null
    public function addIfNotExist($id, $name)
    {
        if ($id == null) {
            $this->insert([
                'name' => $name,
            ]);
            return $this->getInsertID();
        } else {
            return $id;
        }
    }
}
