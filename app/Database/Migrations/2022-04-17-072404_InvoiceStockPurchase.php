<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InvoiceStockPurchase extends Migration
{
    public function up()
    {
        /**
         * Create table invoice_stock_purchases
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'invoice_no' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'supplier' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'total' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'discount' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'grand_total' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'payment_type' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'description' => '0 = cash, 1 = credit',
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'description' => 'User who created this record',
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
        $this->forge->addKey('invoice_no');
        $this->forge->addKey('supplier');
        $this->forge->addKey('date');
        $this->forge->addKey('user_id');
        $this->forge->createTable('invoice_stock_purchases');
    }

    public function down()
    {
        // drop table invoice_stock_purchases
        $this->forge->dropTable('invoice_stock_purchases');
    }
}
