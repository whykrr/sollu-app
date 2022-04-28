<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StockOpname extends Migration
{
    public function up()
    {
        /**
         * create table stock_opname
         * (date, note, user_id, created_at, updated_at)
         */
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
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
        $this->forge->addKey('date');
        $this->forge->createTable('stock_opname');

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
            'stock_opname_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'before' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'after' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'lost' => [
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
        $this->forge->addKey('stock_opname_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_opname_details');
    }

    public function down()
    {
        // drop table
        $this->forge->dropTable('stock_opname');

        $this->forge->dropTable('stock_opname_details');
    }
}
