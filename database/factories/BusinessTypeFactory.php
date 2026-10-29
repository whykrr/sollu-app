<?php

namespace Database\Factories;

use App\Enums\FeatureEnum;
use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessType>
 */
class BusinessTypeFactory extends Factory
{
    protected $model = BusinessType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'minimarket',
            'name' => 'Minimarket',
            'is_visible' => true,
            'sort_order' => 1,
            'features' => array_column(FeatureEnum::cases(), 'value'),
        ];
    }

    /**
     * Set specific business type code and details.
     */
    public function forType(string $code = 'minimarket', ?string $name = null): static
    {
        return $this->state(fn () => [
            'code' => $code,
            'name' => $name ?? ucfirst(str_replace('_', ' ', $code)),
        ]);
    }
}
