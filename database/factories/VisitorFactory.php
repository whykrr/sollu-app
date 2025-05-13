<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visitor>
 */
class VisitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-6 month', 'now');

        $urls = [
            'https://example.com/home',
            'https://example.com/about',
            'https://example.com/contact',
            'https://example.com/blog',
            'https://example.com/profile',
            'https://example.com/visi-misi',
            'https://example.com/announce',
        ];

        return [
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'url' => fake()->randomElement($urls),
            'referrer' => fake()->url(),
            'session_id' => fake()->uuid(),
            'created_month' => $date->format("Y-m"),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
