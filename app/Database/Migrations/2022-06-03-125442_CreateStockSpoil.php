<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockSpoil extends Migration
{
    public function up()
    {
        /**
         * create table stock_spoil
         * (date, note, user_id, created_at, updated_at)
         */
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'no' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'note' => [
                'type' => 'TEXT',
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addKey('no');
        $this->forge->addKey('date');
        $this->forge->createTable('stock_spoil');

        /**
         * create table stock_opname_details
         * (id, stock_opname_id, product_id, before, after, lost, note, created_at, updated_at)
         */
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'stock_spoil_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'system_stock' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'waste_stock' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'note' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('stock_spoil_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_spoil_details');
    }

    public function down()
    {
        // drop table stock_spoil
        $this->forge->dropTable('stock_spoil');

        // drop table stock_spoil_details
        $this->forge->dropTable('stock_spoil_details');
    }
}
