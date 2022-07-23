<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCogsStockSales extends Migration
{
    public function up()
    {
        // add column reference_id to stock_out
        $this->forge->addColumn('stock_sales', [
            'cogs' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'default' => 0,
                'after' => 'qty',
            ],
        ]);
    }

    public function down()
    {
        // delete column reference_id from stock_out
        $this->forge->dropColumn('stock_sales', 'cogs');
    }
}
