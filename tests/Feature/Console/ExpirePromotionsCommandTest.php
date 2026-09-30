<?php

namespace Tests\Feature\Console;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Promotion\Promotion;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePromotionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        /** @var Business $business */
        $business = Business::create([
            'name' => 'Expire Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_expire_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
        $this->business = $business;

        /** @var User $user */
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Owner',
            'email' => 'user_expire_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);
        $this->user = $user;
    }

    public function test_promotions_ended_before_today_are_marked_as_expired(): void
    {
        // 1. Active promo ended yesterday (should expire)
        $pastActivePromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Past Active Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDay()->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        // 2. Inactive promo ended 3 days ago (should expire)
        $pastInactivePromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Past Inactive Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDays(3)->toDateString(),
            'status' => PromotionStatus::Inactive->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        // 3. Active promo ending today (should NOT expire yet)
        $todayActivePromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Today Active Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->subDays(3)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        // 4. Active promo ending next week (should NOT expire)
        $futureActivePromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Future Active Promo',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->artisan('promotions:expire')
            ->expectsOutputToContain('Successfully expired 2 promotions.')
            ->assertSuccessful();

        $this->assertEquals(PromotionStatus::Expired, $pastActivePromo->fresh()->status);
        $this->assertEquals(PromotionStatus::Expired, $pastInactivePromo->fresh()->status);
        $this->assertEquals(PromotionStatus::Active, $todayActivePromo->fresh()->status);
        $this->assertEquals(PromotionStatus::Active, $futureActivePromo->fresh()->status);
    }

    public function test_promotions_expire_supports_dry_run(): void
    {
        $pastActivePromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Past Active Promo Dry Run',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDay()->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->artisan('promotions:expire --dry-run')
            ->expectsOutputToContain('[DRY RUN] Found 1 promotions that would be marked as expired.')
            ->assertSuccessful();

        $this->assertEquals(PromotionStatus::Active, $pastActivePromo->fresh()->status);
    }
}
