<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CashierLog extends Migration
{
    public function up()
    {
        /**
         * cretae table cashier_log
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'start_time' => [
                'type' => 'TIME',
            ],
            'end_time' => [
                'type' => 'TIME',
            ],
            'begining_balance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'total_sales' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'ending_balance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'start_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'end_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '0 = open, 1 = close',
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
        $this->forge->addKey('start_by');
        $this->forge->addKey('end_by');
        $this->forge->createTable('cashier_logs');
    }

    public function down()
    {
        // drop table cashier_logs
        $this->forge->dropTable('cashier_logs');
    }
}
