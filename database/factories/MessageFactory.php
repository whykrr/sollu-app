<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 years', 'now');
        return [
            'name' => fake('id')->name(),
            'email' => fake('id')->unique()->safeEmail(),
            'subject' => fake('id')->sentence(),
            'message' => fake('id')->paragraph(),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
