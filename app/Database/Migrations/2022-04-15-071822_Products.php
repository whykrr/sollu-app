<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Products extends Migration
{
    public function up()
    {
        /**
         * Create table products 
         * 
         * fields(id,code, name, unit_id, cogs, selling_price, description)
         */
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'barcode' => [
                'type' => 'VARCHAR',
                'constraint' => '225',
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'unit_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'stock' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'stock_min' => [
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
            'description' => [
                'type' => 'TEXT',
            ],
            'photo' => [
                'type' => 'text',
            ],
            'barcode_file' => [
                'type' => 'text',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('barcode', false, true);
        $this->forge->addKey('name', false, false);
        $this->forge->createTable('products');
    }

    public function down()
    {
        // drop table products table
        $this->forge->dropTable('products');
    }
}
