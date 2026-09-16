<?php

namespace Database\Factories;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'module' => 'sales',
            'group' => 'Penjualan',
            'group_label' => 'Penjualan & Kasir',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
