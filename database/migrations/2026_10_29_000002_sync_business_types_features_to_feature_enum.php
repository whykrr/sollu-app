<?php

use App\Enums\FeatureEnum;
use App\Models\BusinessType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map of legacy feature codes to valid FeatureEnum values.
     *
     * @var array<string, string>
     */
    protected array $legacyToCurrent = [
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

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $validFeatureCodes = array_map(fn (FeatureEnum $f) => $f->value, FeatureEnum::cases());

        $businessTypes = DB::table('business_types')->get();

        foreach ($businessTypes as $bt) {
            if ($bt->features === null) {
                continue;
            }

            $features = json_decode($bt->features, true);
            if (! is_array($features)) {
                continue;
            }

            $mapped = [];
            foreach ($features as $code) {
                $targetCode = $this->legacyToCurrent[$code] ?? $code;
                if (in_array($targetCode, $validFeatureCodes, true)) {
                    $mapped[] = $targetCode;
                }
            }

            $mapped = array_values(array_unique($mapped));

            DB::table('business_types')
                ->where('id', $bt->id)
                ->update(['features' => json_encode($mapped)]);
        }

        BusinessType::clearCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $currentToLegacy = array_flip($this->legacyToCurrent);

        $businessTypes = DB::table('business_types')->get();

        foreach ($businessTypes as $bt) {
            if ($bt->features === null) {
                continue;
            }

            $features = json_decode($bt->features, true);
            if (! is_array($features)) {
                continue;
            }

            $mapped = [];
            foreach ($features as $code) {
                $mapped[] = $currentToLegacy[$code] ?? $code;
            }

            $mapped = array_values(array_unique($mapped));

            DB::table('business_types')
                ->where('id', $bt->id)
                ->update(['features' => json_encode($mapped)]);
        }

        BusinessType::clearCache();
    }
};
