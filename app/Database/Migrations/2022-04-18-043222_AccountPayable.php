<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AccountPayable extends Migration
{
    public function up()
    {
        /**
         * create table account_payable
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
                'null' => false,
            ],
            'invoice_id' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'supplier' => [
                'type' => 'VARCHAR',
                'constraint' => 225,
                'null' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => '0',
                'comment' => '0 = unpaid, 1 = paid',
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
        $this->forge->addKey('invoice_id');
        $this->forge->addKey('supplier');
        $this->forge->addKey('due_date');
        $this->forge->addKey('status');
        $this->forge->createTable('account_payable');
    }

    public function down()
    {
        // drop table account_payable
        $this->forge->dropTable('account_payable');
    }
}
