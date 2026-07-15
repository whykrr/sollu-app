<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ModifierGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterProductSeeder extends Seeder
{
    public function run(): void
    {
        // Must run after MinimarketCategorySeeder & MasterModifierSeeder
        $business = Business::where('email', 'sollu.mart@email.com')->first();
        if (!$business) return;

        $catSnack = ProductCategory::where('business_id', $business->id)->where('name', 'Makanan Ringan')->first();
        $catDrink = ProductCategory::where('business_id', $business->id)->where('name', 'Minuman Dingin')->first();
        $catGroceries = ProductCategory::where('business_id', $business->id)->where('name', 'Kebutuhan Dapur')->first();
        $catCare = ProductCategory::where('business_id', $business->id)->where('name', 'Perawatan Diri')->first();
        $catCoffee = ProductCategory::where('business_id', $business->id)->where('name', 'Point Coffee')->first();
        $catRte = ProductCategory::where('business_id', $business->id)->where('name', 'Ready to Eat')->first();
        $catService = ProductCategory::where('business_id', $business->id)->where('name', 'Jasa & PPOB')->first();

        $modIce = ModifierGroup::where('business_id', $business->id)->where('name', 'Pilihan Es (Point Coffee)')->first();
        $modSugar = ModifierGroup::where('business_id', $business->id)->where('name', 'Tingkat Kemanisan (Point Coffee)')->first();
        $modTopping = ModifierGroup::where('business_id', $business->id)->where('name', 'Topping Makanan (RTE)')->first();
        $modBag = ModifierGroup::where('business_id', $business->id)->where('name', 'Kantong Belanja')->first();

        // Safe fallback if categories not seeded yet (though they should be)
        $defaultCatId = $catSnack?->id;

        $productsData = [
            // 1. Basic (No Variant)
            [
                'name' => 'Chitato Sapi Panggang 68g',
                'code' => 'SNK-001',
                'category_id' => $catSnack?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 12500,
            ],
            [
                'name' => 'Taro Net Seaweed 65g',
                'code' => 'SNK-002',
                'category_id' => $catSnack?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 10000,
            ],
            [
                'name' => 'Aqua Botol 600ml',
                'code' => 'DRK-001',
                'category_id' => $catDrink?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 3500,
            ],
            [
                'name' => 'Pocari Sweat 500ml',
                'code' => 'DRK-002',
                'category_id' => $catDrink?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 7500,
            ],
            [
                'name' => 'Indomie Goreng Special',
                'code' => 'GRC-001',
                'category_id' => $catGroceries?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 3200,
            ],
            [
                'name' => 'Bimoli Minyak Goreng 2L',
                'code' => 'GRC-002',
                'category_id' => $catGroceries?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 38000,
            ],
            [
                'name' => 'Rinso Anti Noda 700g',
                'code' => 'CAR-001',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 24000,
            ],
            [
                'name' => 'Pepsodent White 190g',
                'code' => 'CAR-002',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 12500,
            ],

            // 2. F&B with Variants & Modifiers
            [
                'name' => 'Kopi Susu Gula Aren',
                'code' => 'COF-001',
                'category_id' => $catCoffee?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => true,
                'has_modifier' => true,
                'track_inventory' => false, // usually tracked by recipe
                'price' => 15000,
                'modifiers' => [$modIce?->id, $modSugar?->id],
            ],
            [
                'name' => 'Americano',
                'code' => 'COF-002',
                'category_id' => $catCoffee?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => true,
                'has_modifier' => true,
                'track_inventory' => false,
                'price' => 12000,
                'modifiers' => [$modIce?->id, $modSugar?->id],
            ],
            [
                'name' => 'Matcha Latte',
                'code' => 'COF-003',
                'category_id' => $catCoffee?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => true,
                'has_modifier' => true,
                'track_inventory' => false,
                'price' => 18000,
                'modifiers' => [$modIce?->id, $modSugar?->id],
            ],

            // 3. Ready to Eat with Modifiers
            [
                'name' => 'Nasi Goreng Sosis RTE',
                'code' => 'RTE-001',
                'category_id' => $catRte?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => true,
                'track_inventory' => true,
                'price' => 22000,
                'modifiers' => [$modTopping?->id],
            ],
            [
                'name' => 'Mie Goreng Spesial RTE',
                'code' => 'RTE-002',
                'category_id' => $catRte?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => true,
                'track_inventory' => true,
                'price' => 20000,
                'modifiers' => [$modTopping?->id],
            ],
            [
                'name' => 'Spaghetti Bolognese RTE',
                'code' => 'RTE-003',
                'category_id' => $catRte?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => false,
                'has_modifier' => true,
                'track_inventory' => true,
                'price' => 25000,
                'modifiers' => [$modTopping?->id],
            ],

            // 4. Retail with Variants
            [
                'name' => 'Kaos Kaki Katun',
                'code' => 'FSH-001',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => true,
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 15000,
            ],
            [
                'name' => 'Masker Medis 3Ply',
                'code' => 'FSH-002',
                'category_id' => $catCare?->id ?? $defaultCatId,
                'type' => 'basic',
                'has_variant' => true, // Color variants
                'has_modifier' => false,
                'track_inventory' => true,
                'price' => 20000,
            ],

            // 5. Services
            [
                'name' => 'Top-up Saldo e-Money',
                'code' => 'SRV-001',
                'category_id' => $catService?->id ?? $defaultCatId,
                'type' => 'service',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => false,
                'price' => 2000, // Admin fee
            ],
            [
                'name' => 'Pembayaran Tagihan Listrik',
                'code' => 'SRV-002',
                'category_id' => $catService?->id ?? $defaultCatId,
                'type' => 'service',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => false,
                'price' => 3000, // Admin fee
            ],

            // 6. Bundles
            [
                'name' => 'Paket Hemat Ngemil',
                'code' => 'BND-001',
                'category_id' => $catSnack?->id ?? $defaultCatId,
                'type' => 'bundle',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => false, // Handled by components
                'price' => 25000, // Bundle price
            ],
            [
                'name' => 'Paket Sembako Murah',
                'code' => 'BND-002',
                'category_id' => $catGroceries?->id ?? $defaultCatId,
                'type' => 'bundle',
                'has_variant' => false,
                'has_modifier' => false,
                'track_inventory' => false,
                'price' => 50000, // Bundle price
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
                    'has_variant' => $pd['has_variant'] ?? false,
                    'has_modifier' => $pd['has_modifier'] ?? false,
                    'has_recipe' => false,
                    'track_inventory' => $pd['track_inventory'] ?? false,
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

            // Connect Modifiers
            if ($product->has_modifier && !empty($pd['modifiers'])) {
                $product->modifierGroups()->sync(array_filter($pd['modifiers']));
            }

            // Create Variants
            if ($product->has_variant && $product->product_type === 'basic') {
                if (str_starts_with($pd['code'], 'COF')) {
                    $vg = $product->variantGroups()->updateOrCreate(['name' => 'Ukuran Gelas'], ['sort_order' => 0]);
                    $opt1 = $vg->options()->updateOrCreate(['name' => 'Regular'], ['sort_order' => 0]);
                    $opt2 = $vg->options()->updateOrCreate(['name' => 'Large'], ['sort_order' => 1]);
                } else {
                    $vg = $product->variantGroups()->updateOrCreate(['name' => 'Warna'], ['sort_order' => 0]);
                    $opt1 = $vg->options()->updateOrCreate(['name' => 'Putih'], ['sort_order' => 0]);
                    $opt2 = $vg->options()->updateOrCreate(['name' => 'Hitam'], ['sort_order' => 1]);
                }

                // Inventory Items for Variant
                $inv1 = \App\Models\Master\InventoryItem::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'sku' => $product->code . '-1',
                    ],
                    [
                        'item_type' => 'variant_sku',
                        'track_inventory' => $product->track_inventory,
                        'min_stock'       => rand(3, 10),
                    ]
                );
                $inv1->variantGroupOptions()->sync([$opt1->id]);

                $inv2 = \App\Models\Master\InventoryItem::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'sku' => $product->code . '-2',
                    ],
                    [
                        'item_type' => 'variant_sku',
                        'track_inventory' => $product->track_inventory,
                        'min_stock'       => rand(3, 10),
                    ]
                );
                $inv2->variantGroupOptions()->sync([$opt2->id]);
            } elseif ($product->product_type === 'basic') {
                // Base Inventory Item for non-variant products
                \App\Models\Master\InventoryItem::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'sku' => $product->code,
                    ],
                    [
                        'item_type' => 'variant_sku',
                        'track_inventory' => $product->track_inventory,
                        'min_stock'       => rand(3, 10),
                    ]
                );
            }
        }
    }
}
