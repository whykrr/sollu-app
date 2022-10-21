<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'datetime' => [
                'type' => 'DATETIME',
            ],
            'stock_in' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'stock_out' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('stock_logs', true);
    }
}
