<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Models\ProductsModel;

class Product extends BaseController
{
    public function index()
    {
        $data['sidebar_active'] = 'report-product';

        // instance model
        $products = new ProductsModel();

        $data['report'] = $products
            ->select('products.*, units.name as unit_name, SUM(stock_sales.qty) as total_sales')
            ->join('stock_sales', 'stock_sales.product_id = products.id', 'left')
            ->join('units', 'units.id = products.unit_id', 'left')
            ->orderBy('total_sales', 'DESC')
            ->groupBy('products.id')
            ->like('stock_sales.created_at', date('Y-m'), 'after')
            ->findAll();

        return view('report/list_product', $data);
    }
}
