<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StockPurchases extends Migration
{
    public function up()
    {
        /**
         * Create table stock_purchases
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'invoice_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'qty' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'cogs' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'selling_price' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('invoice_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_purchases');
    }

    public function down()
    {
        // drop table stock_purchases
        $this->forge->dropTable('stock_purchases');
    }
}
