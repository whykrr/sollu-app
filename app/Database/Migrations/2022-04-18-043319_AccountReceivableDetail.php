<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AccountReceivableDetail extends Migration
{
    public function up()
    {
        /**
         * create table account_receivable_details
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'account_receivable_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('account_receivable_id');
        $this->forge->createTable('account_receivable_details');
    }

    public function down()
    {
        // drop table account_receivable_details
        $this->forge->dropTable('account_receivable_details');
    }
}
