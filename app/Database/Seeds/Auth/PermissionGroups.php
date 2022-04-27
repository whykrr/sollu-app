<?php

namespace App\Database\Seeds\Auth;

use CodeIgniter\Database\Seeder;

class PermissionGroups extends Seeder
{
    public function run()
    {
        // instance db
        $db = \Config\Database::connect();

        // empty table auth_groups
        $db->table('auth_groups')->emptyTable();

        // reset auto increment
        $db->query('ALTER TABLE auth_groups AUTO_INCREMENT = 1');

        //empty table auth_groups_permissions
        $db->table('auth_groups_permissions')->emptyTable();

        // reset auto increment
        $db->query('ALTER TABLE auth_groups_permissions AUTO_INCREMENT = 1');

        // create seeders table auth_groups
        $auth = service('authorization');

        $auth->createGroup('superadmin', 'Superadmin');
        $auth->createGroup('owner', 'Owner');
        $auth->createGroup('admin', 'Admin');
        $auth->createGroup('warehouse', 'Kepala Gudang');
        $auth->createGroup('user', 'Kasir');

        $auth->addPermissionToGroup('master-data', 'superadmin');
        $auth->addPermissionToGroup('master-data-unit', 'superadmin');
        $auth->addPermissionToGroup('master-data-prodcategory', 'superadmin');
        $auth->addPermissionToGroup('master-data-product', 'superadmin');
        $auth->addPermissionToGroup('user', 'superadmin');
        $auth->addPermissionToGroup('inventory', 'superadmin');
        $auth->addPermissionToGroup('inventory-stock', 'superadmin');
        $auth->addPermissionToGroup('inventory-stock-purchase', 'superadmin');
        $auth->addPermissionToGroup('cashier', 'superadmin');
        $auth->addPermissionToGroup('sales', 'superadmin');
        $auth->addPermissionToGroup('finance', 'superadmin');
        $auth->addPermissionToGroup('finance-income', 'superadmin');
        $auth->addPermissionToGroup('finance-expense', 'superadmin');
        $auth->addPermissionToGroup('debtcredit', 'superadmin');
        $auth->addPermissionToGroup('debtcredit-debt', 'superadmin');
        $auth->addPermissionToGroup('debtcredit-credit', 'superadmin');
        $auth->addPermissionToGroup('report', 'superadmin');
        $auth->addPermissionToGroup('report-product', 'superadmin');
        $auth->addPermissionToGroup('report-receipt', 'superadmin');

        $auth->addPermissionToGroup('master-data', 'owner');
        $auth->addPermissionToGroup('master-data-unit', 'owner');
        $auth->addPermissionToGroup('master-data-prodcategory', 'owner');
        $auth->addPermissionToGroup('master-data-product', 'owner');
        $auth->addPermissionToGroup('user', 'owner');
        $auth->addPermissionToGroup('inventory', 'owner');
        $auth->addPermissionToGroup('inventory-stock', 'owner');
        $auth->addPermissionToGroup('inventory-stock-purchase', 'owner');
        $auth->addPermissionToGroup('sales', 'owner');
        $auth->addPermissionToGroup('finance', 'owner');
        $auth->addPermissionToGroup('finance-income', 'owner');
        $auth->addPermissionToGroup('finance-expense', 'owner');
        $auth->addPermissionToGroup('debtcredit', 'owner');
        $auth->addPermissionToGroup('debtcredit-debt', 'owner');
        $auth->addPermissionToGroup('debtcredit-credit', 'owner');
        $auth->addPermissionToGroup('report', 'owner');
        $auth->addPermissionToGroup('report-product', 'owner');
        $auth->addPermissionToGroup('report-receipt', 'owner');

        $auth->addPermissionToGroup('master-data', 'admin');
        $auth->addPermissionToGroup('master-data-unit', 'admin');
        $auth->addPermissionToGroup('master-data-prodcategory', 'admin');
        $auth->addPermissionToGroup('master-data-product', 'admin');
        $auth->addPermissionToGroup('user', 'admin');
        $auth->addPermissionToGroup('inventory', 'admin');
        $auth->addPermissionToGroup('inventory-stock', 'admin');
        $auth->addPermissionToGroup('inventory-stock-purchase', 'admin');
        $auth->addPermissionToGroup('cashier', 'admin');
        $auth->addPermissionToGroup('sales', 'admin');
        $auth->addPermissionToGroup('finance', 'admin');
        $auth->addPermissionToGroup('finance-income', 'admin');
        $auth->addPermissionToGroup('finance-expense', 'admin');
        $auth->addPermissionToGroup('debtcredit', 'admin');
        $auth->addPermissionToGroup('debtcredit-debt', 'admin');
        $auth->addPermissionToGroup('debtcredit-credit', 'admin');
        $auth->addPermissionToGroup('report', 'admin');
        $auth->addPermissionToGroup('report-product', 'admin');
        $auth->addPermissionToGroup('report-receipt', 'admin');

        $auth->addPermissionToGroup('master-data', 'warehouse');
        $auth->addPermissionToGroup('master-data-unit', 'warehouse');
        $auth->addPermissionToGroup('master-data-prodcategory', 'warehouse');
        $auth->addPermissionToGroup('master-data-product', 'warehouse');
        $auth->addPermissionToGroup('inventory', 'warehouse');
        $auth->addPermissionToGroup('inventory-stock', 'warehouse');
        $auth->addPermissionToGroup('inventory-stock-purchase', 'warehouse');
        $auth->addPermissionToGroup('cashier', 'warehouse');
        $auth->addPermissionToGroup('sales', 'warehouse');
        $auth->addPermissionToGroup('report', 'warehouse');
        $auth->addPermissionToGroup('report-product', 'warehouse');

        $auth->addPermissionToGroup('master-data', 'user');
        $auth->addPermissionToGroup('master-data-unit', 'user');
        $auth->addPermissionToGroup('master-data-prodcategory', 'user');
        $auth->addPermissionToGroup('master-data-product', 'user');
        $auth->addPermissionToGroup('inventory', 'user');
        $auth->addPermissionToGroup('inventory-stock', 'user');
        $auth->addPermissionToGroup('inventory-stock-purchase', 'user');
        $auth->addPermissionToGroup('cashier', 'user');
        $auth->addPermissionToGroup('sales', 'user');
        $auth->addPermissionToGroup('report', 'user');
        $auth->addPermissionToGroup('report-product', 'user');
    }
}
