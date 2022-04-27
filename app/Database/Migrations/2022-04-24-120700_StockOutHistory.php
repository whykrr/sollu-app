<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StockOutHistory extends Migration
{
    public function up()
    {
        /**
         * create stock_out
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
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
            ],
            'cogs' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_out');
    }

    public function down()
    {
        /**
         * drop stock_out
         */
        $this->forge->dropTable('stock_out');
    }
}
