<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterStockSalesV2 extends Migration
{
    public function up()
    {
        // add column customer_id
        $this->forge->addColumn('invoice_stock_sales', [
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'return'
            ]
        ]);
    }

    public function down()
    {
        // remove column customer_id
        $this->forge->dropColumn('invoice_stock_sales', 'customer_id');
    }
}
