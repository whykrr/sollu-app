<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterStockPurchase extends Migration
{
    public function up()
    {
        // add column supplier_id
        $this->forge->addColumn('invoice_stock_purchases', [
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'invoice_no'
            ]
        ]);
    }

    public function down()
    {
        // remove column supplier_id
        $this->forge->dropColumn('invoice_stock_purchases', 'supplier_id');
    }
}
