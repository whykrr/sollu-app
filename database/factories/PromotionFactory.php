<?php

namespace Database\Factories;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Promotion\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => function () {
                $businessType = BusinessType::first() ?? BusinessType::create([
                    'code' => 'retail',
                    'name' => 'Retail',
                    'is_visible' => true,
                    'sort_order' => 1,
                ]);

                return Business::create([
                    'name' => fake()->company(),
                    'owner_name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'business_type_id' => $businessType->id,
                    'trial_end_at' => now()->addDays(14),
                ])->id;
            },
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'application_mode' => PromotionApplicationMode::Automatic,
            'promo_code' => null,
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'max_discount_amount' => 50000.0,
            'min_subtotal' => 0.0,
            'min_quantity' => 1.0,
            'applies_to_all_outlets' => true,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'days_of_week' => null,
            'status' => PromotionStatus::Draft,
            'published_by' => null,
            'published_at' => null,
            'created_by' => fn (array $attributes) => User::factory()->create([
                'business_id' => $attributes['business_id'],
            ])->id,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::Active,
            'published_at' => now(),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::Inactive,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::Expired,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function manual(?string $code = null): self
    {
        return $this->state(fn (array $attributes) => [
            'application_mode' => PromotionApplicationMode::Manual,
            'promo_code' => $code ?? strtoupper(fake()->bothify('PROMO-###??')),
        ]);
    }

    public function fixed(float $amount = 15000.0): self
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => PromotionDiscountType::Fixed,
            'discount_value' => $amount,
            'max_discount_amount' => null,
        ]);
    }

    public function categoryScope(): self
    {
        return $this->state(fn (array $attributes) => [
            'target_scope' => PromotionTargetScope::Category,
        ]);
    }

    public function productScope(): self
    {
        return $this->state(fn (array $attributes) => [
            'target_scope' => PromotionTargetScope::Product,
        ]);
    }

    public function variantScope(): self
    {
        return $this->state(fn (array $attributes) => [
            'target_scope' => PromotionTargetScope::Variant,
        ]);
    }
}
