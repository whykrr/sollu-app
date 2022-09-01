<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomer extends Migration
{
    public function up()
    {
        /**
         * create table customers
         * field (id, name, address, phone, created_at, updated_at)
         */

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
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
        $this->forge->addKey('phone', true);
        $this->forge->createTable('customers');
    }

    public function down()
    {
        // drop table custommers
        $this->forge->dropTable('customers', true);
    }
}
