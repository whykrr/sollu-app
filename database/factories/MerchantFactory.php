<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settings>
 */
class MerchantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'               => fake('ID')->company(),
            'owner_name'         => fake('ID')->name(),
            'email'              => fake('ID')->companyEmail(),
            'phone'              => fake('ID')->phoneNumber(),
            'address'            => fake('ID')->address(),
            'logo_url'           => fake()->imageUrl(),
            'already_free_trial' => false,
            'merchant_type_id'   => MerchantType::factory(),
            'settings'           => [
                'app' => false,
            ],
        ];
    }
}
