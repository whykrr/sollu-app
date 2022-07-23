<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterInvoiceStockSales extends Migration
{
    public function up()
    {
        // add column type to table invoice_stock_sales
        $this->forge->addColumn('invoice_stock_sales', [
            'type' => [
                'type' => 'varchar',
                'constraint' => 20,
                'null' => false,
                'after' => 'invoice_no',
                'default' => 'sales'
            ],
            'note' => [
                'type' => 'text',
                'null' => true,
                'after' => 'customer'
            ]
        ]);
        // add index idx_type to table invoice_stock_sales
        $this->db->query('ALTER TABLE invoice_stock_sales ADD INDEX idx_type (type)');
    }

    public function down()
    {
        // drop column type to table invoice_stock_sales
        $this->forge->dropColumn('invoice_stock_sales', 'type');
    }
}
