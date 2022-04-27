<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Settings extends Migration
{
    public function up()
    {
        /**
         * create table setting
         * (key, category, value, created_at, updated_at)
         */
        $this->forge->addField([
            'key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'desc' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('key', true);
        $this->forge->addKey('category');
        $this->forge->createTable('settings');
    }

    public function down()
    {
        // drop table setting
        $this->forge->dropTable('settings');
    }
}
