<?php

namespace App\Models;

use CodeIgniter\Model;

class StockLogModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'stock_logs';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        "description",
        "product_id",
        "datetime",
        "stock_in",
        "stock_out",
        "cogs",
        "selling_price",
    ];

    // Dates
    protected $useTimestamps        = true;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = false;
    protected $deletedField         = false;

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

    public static function StockIN($desc, $data, $dated = null)
    {
        if ($dated == null) {
            $dated = date("Y-m-d");
        }

        $insert = [];

        foreach ($data as $key => $value) {
            $insert[] = [
                "description" => $desc,
                "product_id" => $value["product_id"],
                "datetime" => date("Y-m-d H:i:s"),
                "stock_in" => $value["stock_in"],
                "stock_out" => 0,
                "cogs" => $value["cogs"],
                "selling_price" => $value["selling_price"],
            ];
        }

        // insert batch
        return (new self())->insertBatch($insert);
    }

    public static function StockOUT($desc, $data, $dated = null)
    {
        if ($dated == null) {
            $dated = date("Y-m-d");
        }

        $insert = [];

        foreach ($data as $key => $value) {
            $insert[] = [
                "description" => $desc,
                "product_id" => $value["product_id"],
                "datetime" => $dated . ' ' . date("H:i:s"),
                "stock_in" => 0,
                "stock_out" => $value["stock_out"],
                "cogs" => $value["cogs"],
                "selling_price" => $value["selling_price"],
            ];
        }

        // insert batch
        return (new self())->insertBatch($insert);
    }
}
