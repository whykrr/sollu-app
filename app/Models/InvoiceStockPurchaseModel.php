<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceStockPurchaseModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'invoice_stock_purchases';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'id',
        'invoice_no',
        'supplier',
        'date',
        'total',
        'discount',
        'grand_total',
        'payment_type',
        'note',
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
        'invoice_no' => [
            'label' => 'No. Invoice',
            'rules' => 'required|alpha_numeric_punct|max_length[255]',
        ],
        'date' => [
            'label' => 'Tanggal',
            'rules' => 'required',
        ],
        'payment_type' => [
            'label' => 'Tipe Pembayaran',
            'rules' => 'required',
        ],
    ];
    protected $validationMessages   = [
        'invoice_no' => [
            'required' => '{field} harus diisi',
            'alpha_numeric_punct' => '{field} hanya boleh berisi huruf, angka, spasi, titik dan garis bawah',
            'max_length' => '{field} maksimal 255 karakter',
        ],
        'date' => [
            'required' => '{field} harus diisi',
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
}
