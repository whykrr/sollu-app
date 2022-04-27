<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountPayableDetailModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'account_payable_details';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = true;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'account_payable_id',
        'date',
        'amount',
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
        'date' => [
            'label' => 'Tanggal',
            'rules' => 'required|valid_date',
        ],
        'amount' => [
            'label' => 'Total',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'date' => [
            'required' => '{field} harus diisi.',
            'valid_date' => '{field} tidak valid.',
        ],
        'amount' => [
            'required' => '{field} harus diisi.',
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
}
