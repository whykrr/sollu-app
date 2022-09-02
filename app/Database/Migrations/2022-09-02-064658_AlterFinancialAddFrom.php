<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterFinancialAddFrom extends Migration
{
    public function up()
    {
        // add source after reference_id on table financials
        $this->forge->addColumn('financials', [
            'source' => [
                'type' => 'varchar',
                'constraint' => 20,
                'null' => true,
                'after' => 'reference_id',
                'default' => 'sales'
            ]
        ]);

        // add index idx_source to table financials
        $this->db->query('ALTER TABLE financials ADD INDEX idx_source (source)');
    }

    public function down()
    {
        // drop index idx_source from table financials
        $this->db->query('ALTER TABLE financials DROP INDEX idx_source');

        // drop column from from table financials
        $this->forge->dropColumn('financials', 'source');
    }
}
