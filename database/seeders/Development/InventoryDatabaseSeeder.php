<?php

namespace Database\Seeders\Development;

use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use Illuminate\Database\Seeder;

class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Seed dummy inventory data for development.
     * Must run after DummyMinimarketSeeder.
     */
    public function run(): void
    {
        $business = Business::where('email', 'sollu.mart@email.com')->first();
        if (! $business) {
            return;
        }

        $outlet = Outlet::where('business_id', $business->id)->first();
        if (! $outlet) {
            return;
        }

        // ── UOMs ─────────────────────────────────────────────────────
        $uomPcs = Uom::where('code', 'Pcs')->firstOrFail();
        $uomKg  = Uom::where('code', 'Kg')->firstOrFail();
        $uomL   = Uom::where('code', 'L')->firstOrFail();
        $uomG   = Uom::where('code', 'G')->firstOrFail();
        $uomMl  = Uom::where('code', 'Ml')->firstOrFail();
        $uomBox = Uom::where('code', 'Box')->firstOrFail();

        // ── Suppliers ────────────────────────────────────────────────
        $suppliers = [
            [
                'name'    => 'PT Indofood Sukses Makmur',
                'phone'   => '021-5795-8822',
                'email'   => 'procurement@indofood.co.id',
                'address' => 'Sudirman Plaza, Jakarta Selatan',
            ],
            [
                'name'    => 'CV Sumber Rejeki',
                'phone'   => '031-555-1234',
                'email'   => 'order@sumberrejeki.com',
                'address' => 'Jl. Raya Darmo No. 45, Surabaya',
            ],
            [
                'name'    => 'PT Unilever Indonesia',
                'phone'   => '021-526-2112',
                'email'   => 'supply@unilever.co.id',
                'address' => 'Graha Unilever, BSD, Tangerang',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::updateOrCreate(
                ['business_id' => $business->id, 'name' => $supplierData['name']],
                array_merge($supplierData, ['business_id' => $business->id])
            );
        }

        // ── Inventory Items (Raw Materials) ──────────────────────────
        $rawMaterials = [
            ['sku' => 'RM-001', 'name_hint' => 'Tepung Terigu Segitiga Biru',  'uom_id' => $uomKg->id,  'minimum_stock' => 10.0000],
            ['sku' => 'RM-002', 'name_hint' => 'Gula Pasir',                   'uom_id' => $uomKg->id,  'minimum_stock' => 5.0000],
            ['sku' => 'RM-003', 'name_hint' => 'Minyak Goreng Bimoli',         'uom_id' => $uomL->id,   'minimum_stock' => 10.0000],
            ['sku' => 'RM-004', 'name_hint' => 'Susu Cair Full Cream',         'uom_id' => $uomL->id,   'minimum_stock' => 5.0000],
            ['sku' => 'RM-005', 'name_hint' => 'Biji Kopi Arabica',            'uom_id' => $uomKg->id,  'minimum_stock' => 2.0000],
            ['sku' => 'RM-006', 'name_hint' => 'Bubuk Coklat',                 'uom_id' => $uomG->id,   'minimum_stock' => 500.0000],
            ['sku' => 'RM-007', 'name_hint' => 'Sirup Vanilla',                'uom_id' => $uomMl->id,  'minimum_stock' => 250.0000],
            ['sku' => 'RM-008', 'name_hint' => 'Cup Plastik 16oz',             'uom_id' => $uomPcs->id, 'minimum_stock' => 100.0000],
            ['sku' => 'RM-009', 'name_hint' => 'Sedotan Biodegradable',        'uom_id' => $uomPcs->id, 'minimum_stock' => 200.0000],
            ['sku' => 'RM-010', 'name_hint' => 'Tissue Makan',                 'uom_id' => $uomBox->id, 'minimum_stock' => 10.0000],
        ];

        foreach ($rawMaterials as $material) {
            $item = InventoryItem::updateOrCreate(
                ['business_id' => $business->id, 'sku' => $material['sku']],
                [
                    'business_id'     => $business->id,
                    'name'            => $material['name_hint'],
                    'item_type'       => 'raw_material',
                    'sku'             => $material['sku'],
                    'uom_id'          => $material['uom_id'],
                    'track_inventory' => true,
                    'minimum_stock'   => $material['minimum_stock'],
                    'is_active'       => true,
                ]
            );

            // Create balance for the outlet
            InventoryBalance::updateOrCreate(
                [
                    'outlet_id'         => $outlet->id,
                    'inventory_item_id' => $item->id,
                ],
                [
                    'business_id'   => $business->id,
                    'outlet_id'     => $outlet->id,
                    'current_stock' => fake()->randomFloat(0, 0, $material['minimum_stock'] * 3),
                ]
            );
        }

        // Call the integrated MasterProductSeeder
        $this->call(MasterProductSeeder::class);
    }
}
