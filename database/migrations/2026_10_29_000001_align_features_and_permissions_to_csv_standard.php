<?php

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename existing features to new codes
        $featureRenames = [
            'pos_cashier' => 'pos',
            'shift_management' => 'shift_cashier',
            'invoice_debt' => 'transaction_invoice',
            'product_variants' => 'product_variant',
            'product_modifiers' => 'product_modifier',
            'recipe_management' => 'product_bom',
            'supplier_management' => 'purchase_supplier',
            'purchase_orders' => 'purchase_order',
            'customer_management' => 'crm',
            'sales_reports' => 'report_sales',
            'product_reports' => 'report_product',
            'stock_reports' => 'report_inventory',
            'cashier_reports' => 'report_shift',
            'audit_logs' => 'audit_log',
        ];

        foreach ($featureRenames as $oldCode => $newCode) {
            DB::table('features')->where('code', $oldCode)->update(['code' => $newCode]);
        }

        // Delete subsumed or deprecated features
        $deprecatedFeatures = [
            'cash_drawer',
            'void_refund',
            'basic_reports',
            'customer_reports',
            'product_categories',
            'outlet_management',
        ];

        $deprecatedFeatureIds = DB::table('features')->whereIn('code', $deprecatedFeatures)->pluck('id');
        DB::table('plan_features')->whereIn('feature_id', $deprecatedFeatureIds)->delete();
        DB::table('features')->whereIn('code', $deprecatedFeatures)->delete();

        // 2. Permission alignment
        // Rename existing permission records
        $permissionRenames = [
            'category.view' => 'product.category.view',
            'category.create' => 'product.category.create',
            'category.update' => 'product.category.update',
            'category.delete' => 'product.category.delete',
            'supplier.view' => 'purchase.supplier.view',
            'supplier.create' => 'purchase.supplier.create',
            'supplier.update' => 'purchase.supplier.update',
            'supplier.delete' => 'purchase.supplier.delete',
            'purchase_order.view' => 'purchase.view',
            'purchase_order.create' => 'purchase.create',
            'purchase_order.update' => 'purchase.update',
            'purchase_order.approve' => 'purchase.validate',
            'purchase_order.cancel' => 'purchase.delete',
            'purchase_order.receive' => 'purchase.receive',
            'purchase_order.return' => 'purchase.return',
            'inventory.adjustment.read' => 'inventory.adjust.view',
            'inventory.adjustment.create' => 'inventory.adjust.create',
            'inventory.adjustment.update' => 'inventory.adjust.update',
            'inventory.adjustment.approve' => 'inventory.adjust.validate',
            'inventory.adjustment.void' => 'inventory.adjust.delete',
            'inventory.opname.read' => 'inventory.opname.view',
            'inventory.opname.approve' => 'inventory.opname.validate',
            'inventory.transfer.read' => 'inventory.transfer.view',
            'inventory.transfer.approve' => 'inventory.transfer.validate',
        ];

        foreach ($permissionRenames as $oldName => $newName) {
            DB::table('permissions')->where('name', $oldName)->update(['name' => $newName]);
        }

        // Delete deprecated permissions and wildcards
        $deprecatedPermissions = [
            'business.*', 'outlet.*', 'product.*', 'inventory.*', 'report.*', 'setting.*',
            'business.setting', 'business.update', 'outlet.switch', 'user.invite',
            'product.variant', 'product.import', 'product.export',
            'service.*', 'service.view', 'service.create', 'service.update', 'service.delete', 'service.import', 'service.export',
            'inventory.movement', 'inventory.purchase', 'inventory.waste', 'inventory.receive', 'inventory.stock_opname',
            'inventory.adjustment.freeze', 'inventory.opname.freeze', 'inventory.adjustment.export', 'inventory.opname.export',
            'report.cashflow', 'report.customer', 'report.all',
            'transaction.cancel', 'transaction.delete', 'transaction.discount',
            'purchase_order.void',
        ];

        DB::table('permissions')->whereIn('name', $deprecatedPermissions)->delete();

        // Clear all permission & feature caches
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Feature::clearCache();
        SubscriptionPlan::clearCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert features
        $featureReverts = [
            'pos' => 'pos_cashier',
            'shift_cashier' => 'shift_management',
            'transaction_invoice' => 'invoice_debt',
            'product_variant' => 'product_variants',
            'product_modifier' => 'product_modifiers',
            'product_bom' => 'recipe_management',
            'purchase_supplier' => 'supplier_management',
            'purchase_order' => 'purchase_orders',
            'crm' => 'customer_management',
            'report_sales' => 'sales_reports',
            'report_product' => 'product_reports',
            'report_inventory' => 'stock_reports',
            'report_shift' => 'cashier_reports',
            'audit_log' => 'audit_logs',
        ];

        foreach ($featureReverts as $newCode => $oldCode) {
            DB::table('features')->where('code', $newCode)->update(['code' => $oldCode]);
        }

        $newFeatureCodes = ['product_price_matrix', 'crm_loyalty', 'double_authorization'];
        $newFeatureIds = DB::table('features')->whereIn('code', $newFeatureCodes)->pluck('id');
        DB::table('plan_features')->whereIn('feature_id', $newFeatureIds)->delete();
        DB::table('features')->whereIn('code', $newFeatureCodes)->delete();

        // Revert permissions
        $permissionReverts = [
            'product.category.view' => 'category.view',
            'product.category.create' => 'category.create',
            'product.category.update' => 'category.update',
            'product.category.delete' => 'category.delete',
            'purchase.supplier.view' => 'supplier.view',
            'purchase.supplier.create' => 'supplier.create',
            'purchase.supplier.update' => 'supplier.update',
            'purchase.supplier.delete' => 'supplier.delete',
            'purchase.view' => 'purchase_order.view',
            'purchase.create' => 'purchase_order.create',
            'purchase.update' => 'purchase_order.update',
            'purchase.validate' => 'purchase_order.approve',
            'purchase.delete' => 'purchase_order.cancel',
            'purchase.receive' => 'purchase_order.receive',
            'purchase.return' => 'purchase_order.return',
            'inventory.adjust.view' => 'inventory.adjustment.read',
            'inventory.adjust.create' => 'inventory.adjustment.create',
            'inventory.adjust.update' => 'inventory.adjustment.update',
            'inventory.adjust.validate' => 'inventory.adjustment.approve',
            'inventory.adjust.delete' => 'inventory.adjustment.void',
            'inventory.opname.view' => 'inventory.opname.read',
            'inventory.opname.validate' => 'inventory.opname.approve',
            'inventory.transfer.view' => 'inventory.transfer.read',
            'inventory.transfer.validate' => 'inventory.transfer.approve',
        ];

        foreach ($permissionReverts as $newName => $oldName) {
            DB::table('permissions')->where('name', $newName)->update(['name' => $oldName]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Feature::clearCache();
        SubscriptionPlan::clearCache();
    }
};
