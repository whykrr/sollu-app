<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancialModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'financials';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'account_id',
        'reference_id',
        'source',
        'type',
        'amount',
        'description',
        'user_id',
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
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
     * get detail financial
     * 
     * @param int $id
     * @return array
     */
    public function getDetail($id)
    {
        $builder = $this->db->table($this->table);
        $builder->select('financials.*, financial_accounts.name as account_name');
        $builder->join('financial_accounts', 'financial_accounts.id = financials.account_id', 'left');
        $builder->where('financials.id', $id);
        $result = $builder->get()->getRowArray();

        return $result;
    }
}
