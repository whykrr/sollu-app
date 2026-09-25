<?php

namespace Database\Factories;

use App\Models\SettingsType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettingsType>
 */
class MerchantTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'Restoran' => 'fnb',
            'Retail' => 'retail',
            'Barbershop' => 'services',
            'Toko Online' => 'online_shop',
        ];

        $name = fake()->randomElement(array_keys($types));

        return [
            'name' => $name,
            'code' => $types[$name],
            'settings' => [
                'email' => fake()->boolean(),
                'sms' => fake()->boolean(),
            ],
        ];
    }
}
