<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemarkSpoil extends Migration
{
    public function up()
    {
        // rename table stock_spoil to stock_spoil_old
        $this->db->query('RENAME TABLE stock_spoil TO stock_spoil_old');
        // rename table stock_spoil_details to stock_spoil
        $this->db->query('RENAME TABLE stock_spoil_details TO stock_spoil');

        // remove stock_spoil_id from stock_spoil
        $this->forge->dropColumn('stock_spoil', 'stock_spoil_id');
        // add date after id and user_id after note
        $this->forge->addColumn('stock_spoil', [
            'date' => [
                'type' => 'date',
                'null' => false,
                'after' => 'id'
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'after' => 'note'
            ]
        ]);
    }

    public function down()
    {
        // remove date from stock_spoil
        $this->forge->dropColumn('stock_spoil', 'date');
        $this->forge->dropColumn('stock_spoil', 'user_id');

        // add stock_spoil_id after id
        $this->forge->addColumn('stock_spoil', [
            'stock_spoil_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'after' => 'id'
            ]
        ]);

        // rename table stock_spoil to stock_spoil_details
        $this->db->query('RENAME TABLE stock_spoil TO stock_spoil_details');
        // rename table stock_spoil_old to stock_spoil
        $this->db->query('RENAME TABLE stock_spoil_old TO stock_spoil');
    }
}
