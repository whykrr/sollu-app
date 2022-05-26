<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierLogModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'cashier_logs';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'date',
        'start_time',
        'end_time',
        'begining_balance',
        'total_sales',
        'ending_balance',
        'start_by',
        'end_by',
        'status',
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'begining_balance' => [
            'label' => 'Saldo Awal',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'begining_balance' => [
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

    public function getLog()
    {
        // find today open
        $today = date('Y-m-d');
        $log = $this->where('date', $today)
            ->where('status', 0)
            ->first();

        if ($log) {
            return $log['id'];
        } else {
            return null;
        }
    }

    public function getDataOpenToday()
    {
        // find today open
        $today = date('Y-m-d');
        $log = $this->where('date', $today)
            ->where('status', 0)
            ->first();

        if ($log) {
            return $log;
        } else {
            return null;
        }
    }
}
