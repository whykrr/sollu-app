<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Outlets extends Migration
{
    public function up()
    {
        /**
         * Create table Outlets
         * 
         * fields(id,name,address,phone,created_at,updated_at,deleted_at)
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('outlets');
    }

    public function down()
    {
        // Drop table Outlets
        $this->forge->dropTable('outlets');
    }
}
