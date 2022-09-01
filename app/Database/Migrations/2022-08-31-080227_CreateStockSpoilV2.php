<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockSpoilV2 extends Migration
{
    public function up()
    {

        $this->forge->addColumn('stock_spoil', [
            'total' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'after' => 'date',
            ]
        ]);

        $this->forge->addColumn('stock_spoil_details', [
            'cogs' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'after' => 'product_id',
            ],
            'qty' => [
                'type' => 'INT',
                'constraint' => '11',
                'after' => 'cogs',
            ],
            'total' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'after' => 'qty',
            ]
        ]);

        $this->forge->dropColumn('stock_spoil_details', 'system_stock');
        $this->forge->dropColumn('stock_spoil_details', 'waste_stock');

        $this->db->query("ALTER TABLE `sollu-pos-test`.`stock_out` 
        MODIFY COLUMN `type` enum('sell','opname','spoil') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'sell' AFTER `cogs`;");
    }

    public function down()
    {

        $this->forge->addColumn('stock_spoil_details', [
            'system_stock' => [
                'type' => 'INT',
                'constraint' => 11,
                'after' => 'product_id',
            ],
            'waste_stock' => [
                'type' => 'INT',
                'constraint' => 11,
                'after' => 'system_stock',
            ],
        ]);

        $this->forge->dropColumn('stock_spoil', 'total');

        $this->forge->dropColumn('stock_spoil_details', 'cogs');
        $this->forge->dropColumn('stock_spoil_details', 'qty');
        $this->forge->dropColumn('stock_spoil_details', 'total');

        $this->db->query("ALTER TABLE `sollu-pos-test`.`stock_out` 
        MODIFY COLUMN `type` enum('sell','opname') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'sell' AFTER `cogs`;");
    }
}
