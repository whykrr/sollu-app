<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StockSales extends Migration
{
    public function up()
    {
        /**
         * create table stock_sales
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
                'null' => true,
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
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'sub_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
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
        $this->forge->createTable('stock_sales');
    }

    public function down()
    {
        // drop table stock_sales
        $this->forge->dropTable('stock_sales');
    }
}
