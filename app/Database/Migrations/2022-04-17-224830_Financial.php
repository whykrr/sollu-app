<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Financial extends Migration
{
    public function up()
    {
        /**
         * create table financials
         */
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'account_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'reference_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => "'in','out'",
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_id' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
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
        $this->forge->addKey('account_id');
        $this->forge->addKey('reference_id');
        $this->forge->createTable('financials');
    }

    public function down()
    {
        // drop table financials
        $this->forge->dropTable('financials');
    }
}
