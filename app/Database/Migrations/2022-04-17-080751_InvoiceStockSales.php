<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InvoiceStockSales extends Migration
{
    public function up()
    {
        /**
         * create table invoice_stock_sales
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
            'cashier_log_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'date' => [
                'type' => 'DATETIME',
                'null' => true,
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
                'comment' => '0 = cash, 1 = credit',
            ],
            'pay' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'null' => true,
            ],
            'return' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'null' => true,
            ],
            'customer' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'User who created this record',
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
        $this->forge->addKey('cashier_log_id');
        $this->forge->addKey('date');
        $this->forge->addKey('user_id');
        $this->forge->createTable('invoice_stock_sales');
    }

    public function down()
    {
        // drop table invoice_stock_sales
        $this->forge->dropTable('invoice_stock_sales');
    }
}
