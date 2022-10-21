<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\StockOutModel;

class StockModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'stocks';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'id',
        'purchase_id',
        'product_id',
        'stock_in',
        'stock_out',
        'cogs',
        'selling_price',
        'description',
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

    public $updateBatchQuery = "";
    /**
     * ANCHOR - get history
     * 
     * @param int $id
     */
    public function getHistory($id)
    {
        $builder = $this->db->table($this->table);
        $builder->select('stocks.*, units.name as unit_name, invoice.invoice_no, invoice.date as invoice_date');
        $builder->join('products', 'products.id = stocks.product_id');
        $builder->join('units', 'units.id = products.unit_id');
        $builder->join('stock_purchases sp', 'sp.id = stocks.purchase_id', 'left');
        $builder->join('invoice_stock_purchases invoice', 'invoice.id = sp.invoice_id', 'left');
        $builder->where('stocks.product_id', $id);
        $builder->where('stocks.stock_in <> stocks.stock_out');
        $builder->orderBy('stocks.created_at', 'DESC');
        $query = $builder->get();

        return $query->getResultArray();
    }

    /**
     * SECTION - Update stock FIFO
     */
    public function updateStockFIFO($items, $desc = '', $dated = null)
    {
        // convert items product id to array
        $product_ids = array_column($items, 'product_id');

        // INFO - Get data stock
        $builder = $this->db->table($this->table);
        $builder->whereIn('product_id', $product_ids);
        $builder->where('stock_in > stock_out');
        $builder->orderBy('created_at', 'ASC');
        $stocks = $builder
            ->select('stocks.*, products.selling_price as psp')
            ->join('products', 'products.id = stocks.product_id')
            ->get()->getResultArray();

        $updateBatch = [];
        $stockOuts = [];

        // INFO - Update stock and get data to insert stock_out
        foreach ($stocks as $keyStock => $valueStock) {
            // check if $items length is 0 and stop loop
            if (count($items) == 0) {
                break;
            } else {
                foreach ($items as $keyItem => $valueItem) {
                    // check if $items product_id is equal to $valueStock product id
                    if ($valueItem['product_id'] == $valueStock['product_id']) {
                        // check remaining stock

                        // check if $valueStock remaining stock is greater than $valueItem stock out
                        if (($valueStock['stock_in'] - $valueStock['stock_out']) > $valueItem['qty']) {
                            // update stock out
                            $updateBatchSingle = [
                                'id' => $valueStock['id'],
                                'stock_out' => $valueStock['stock_out'] + $valueItem['qty'],
                            ];
                            // append to $updateBatch
                            array_push($updateBatch, $updateBatchSingle);

                            // remove $valueItem from $items
                            unset($items[$keyItem]);
                        } else {
                            $remaining = $valueItem['qty'] - ($valueStock['stock_in'] - $valueStock['stock_out']);
                            // update stock out
                            $updateBatchSingle = [
                                'id' => $valueStock['id'],
                                'stock_out' => $valueStock['stock_in'],
                            ];
                            array_push($updateBatch, $updateBatchSingle);

                            // set remaining stock
                            $items[$keyItem]['qty'] = $remaining;
                        }

                        array_push($stockOuts, [
                            'product_id' => $valueStock['product_id'],
                            'stock_out' => $valueItem['qty'],
                            'qty' => $valueItem['qty'],
                            'cogs' => $valueStock['cogs'],
                            'selling_price' => $valueStock['psp'],
                        ]);
                    }
                }
            }
        }

        // TODO - Update batch stock
        $builder = $this->db->table($this->table);
        if (!$builder->updateBatch($updateBatch, 'id')) {
            return false;
        }

        // INFO - Instance StockOutModel
        $stockOut = new StockOutModel();

        // INFO - Insert stock_out
        if (!$stockOut->insertBatch($stockOuts)) {
            return false;
        }

        if (!StockLogModel::StockOUT($desc, $stockOuts, $dated)) {
            return false;
        }

        return true;
    }
    // !SECTION
}
