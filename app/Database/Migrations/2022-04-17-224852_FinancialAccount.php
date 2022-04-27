<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FinancialAccount extends Migration
{
    public function up()
    {
        /**
         * create table financial_accounts
         */
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('financial_accounts');
    }

    public function down()
    {
        // drop table financial_accounts
        $this->forge->dropTable('financial_accounts');
    }
}
