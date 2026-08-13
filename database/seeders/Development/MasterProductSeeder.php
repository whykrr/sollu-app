<?php

namespace Database\Seeders\Development;

use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class MasterProductSeeder extends Seeder
{
    public function run(): void
    {
        // Must run after MinimarketCategorySeeder & MasterModifierSeeder
        $business = Business::where('email', 'sollu.mart@email.com')->first();
        if (! $business) {
            return;
        }

        $outlet = Outlet::where('business_id', $business->id)->first();
        if (! $outlet) {
            return;
        }

        $catSnack = ProductCategory::where('business_id', $business->id)->where('name', 'Makanan Ringan')->first();
        $catDrink = ProductCategory::where('business_id', $business->id)->where('name', 'Minuman Dingin')->first();
        $catGroceries = ProductCategory::where('business_id', $business->id)->where('name', 'Kebutuhan Dapur')->first();
        $catCare = ProductCategory::where('business_id', $business->id)->where('name', 'Perawatan Diri')->first();

        // Fetch default UOM
        $defaultUom = \App\Models\Uom::where('code', 'PCS')->first();
        $uomId = $defaultUom ? $defaultUom->id : null;

        $defaultCatId = $catSnack?->id;

        $productsData = [
            // Retail Products (Basic, no variants)
            [
                'name' => 'Chitato Sapi Panggang 68g',
                'code' => 'SNK-001',
                'category_id' => $catSnack?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 12500,
            ],
            [
                'name' => 'Taro Net Seaweed 65g',
                'code' => 'SNK-002',
                'category_id' => $catSnack?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 10000,
            ],
            [
                'name' => 'Aqua Botol 600ml',
                'code' => 'DRK-001',
                'category_id' => $catDrink?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 3500,
            ],
            [
                'name' => 'Pocari Sweat 500ml',
                'code' => 'DRK-002',
                'category_id' => $catDrink?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 7500,
            ],
            [
                'name' => 'Indomie Goreng Special',
                'code' => 'GRC-001',
                'category_id' => $catGroceries?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 3200,
            ],
            [
                'name' => 'Bimoli Minyak Goreng 2L',
                'code' => 'GRC-002',
                'category_id' => $catGroceries?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 38000,
            ],
            [
                'name' => 'Rinso Anti Noda 700g',
                'code' => 'CAR-001',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 24000,
            ],
            [
                'name' => 'Pepsodent White 190g',
                'code' => 'CAR-002',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'track_inventory' => true,
                'price' => 12500,
            ],
        ];

        foreach ($productsData as $pd) {
            $product = Product::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'code' => $pd['code'],
                ],
                [
                    'product_category_id' => $pd['category_id'],
                    'product_type' => $pd['type'],
                    'has_variant' => false,
                    'has_modifier' => false,
                    'has_recipe' => false,
                    'track_inventory' => $pd['track_inventory'],
                    'name' => $pd['name'],
                    'is_show' => true,
                    'sellable' => true,
                    'purchasable' => false,
                ]
            );

            $product->prices()->updateOrCreate(
                [
                    'outlet_id' => null,
                    'inventory_item_id' => null,
                ],
                [
                    'amount' => $pd['price'],
                ]
            );

            if ($product->product_type === 'basic') {
                $minStock = rand(3, 10);
                // Base Inventory Item for non-variant products
                $item = \App\Models\Inventory\InventoryItem::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'sku' => $product->code,
                    ],
                    [
                        'name' => $product->name,
                        'item_type' => 'variant_sku', // Must be variant_sku or raw_material per DB schema
                        'uom_id' => $uomId,
                        'track_inventory' => $product->track_inventory,
                        'minimum_stock' => $minStock,
                        'is_active' => true,
                    ]
                );

                if ($product->track_inventory) {
                    InventoryBalance::updateOrCreate(
                        [
                            'outlet_id' => $outlet->id,
                            'inventory_item_id' => $item->id,
                        ],
                        [
                            'business_id' => $business->id,
                            'outlet_id' => $outlet->id,
                            'current_stock' => fake()->randomFloat(2, 0, $minStock * 3),
                        ]
                    );
                }
            }
        }
    }
}
