<?php

namespace Database\Seeders\Development;

use App\Models\Business;
use App\Models\Master\ModifierGroup;
use Illuminate\Database\Seeder;

class MasterModifierSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('email', 'sollu.mart@email.com')->first();
        if (!$business) return;

        $modifiers = [
            [
                'name' => 'Pilihan Es (Point Coffee)',
                'selection_type' => 'single',
                'is_required' => true,
                'max_select' => null,
                'options' => [
                    ['name' => 'Normal Ice', 'additional_price' => 0, 'is_default' => true],
                    ['name' => 'Less Ice', 'additional_price' => 0, 'is_default' => false],
                    ['name' => 'No Ice', 'additional_price' => 0, 'is_default' => false],
                ]
            ],
            [
                'name' => 'Tingkat Kemanisan (Point Coffee)',
                'selection_type' => 'single',
                'is_required' => true,
                'max_select' => null,
                'options' => [
                    ['name' => 'Normal Sugar', 'additional_price' => 0, 'is_default' => true],
                    ['name' => 'Less Sugar', 'additional_price' => 0, 'is_default' => false],
                    ['name' => 'No Sugar', 'additional_price' => 0, 'is_default' => false],
                ]
            ],
            [
                'name' => 'Topping Makanan (RTE)',
                'selection_type' => 'multi',
                'is_required' => false,
                'max_select' => 2,
                'options' => [
                    ['name' => 'Extra Keju', 'additional_price' => 3000, 'is_default' => false],
                    ['name' => 'Extra Sosis', 'additional_price' => 4000, 'is_default' => false],
                    ['name' => 'Extra Telur', 'additional_price' => 3000, 'is_default' => false],
                ]
            ],
            [
                'name' => 'Kantong Belanja',
                'selection_type' => 'single',
                'is_required' => false,
                'max_select' => null,
                'options' => [
                    ['name' => 'Kantong Plastik Kecil', 'additional_price' => 200, 'is_default' => false],
                    ['name' => 'Kantong Plastik Besar', 'additional_price' => 500, 'is_default' => false],
                    ['name' => 'Tas Kain Reusable', 'additional_price' => 5000, 'is_default' => false],
                ]
            ],
        ];

        foreach ($modifiers as $mod) {
            $group = ModifierGroup::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $mod['name'],
                ],
                [
                    'selection_type' => $mod['selection_type'],
                    'is_required' => $mod['is_required'],
                    'max_select' => $mod['max_select'],
                ]
            );

            // Clean up existing options to ensure idempotency when removing options
            // but for simplicity, we'll just updateOrCreate here.
            foreach ($mod['options'] as $opt) {
                $group->options()->updateOrCreate(
                    [
                        'name' => $opt['name'],
                    ],
                    [
                        'additional_price' => $opt['additional_price'],
                        'is_default' => $opt['is_default'],
                    ]
                );
            }
        }
    }
}
