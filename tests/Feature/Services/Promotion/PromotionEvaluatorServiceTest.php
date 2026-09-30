<?php

namespace Tests\Feature\Services\Promotion;

use App\DTOs\Promotion\CartEvaluationDTO;
use App\Enums\ProductTypeEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Promotion\Promotion;
use App\Models\User;
use App\Services\App\Promotion\PromotionEvaluatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionEvaluatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEvaluatorService $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new PromotionEvaluatorService;
    }

    protected function createBusiness(string $name = 'Test Business'): Business
    {
        $businessType = BusinessType::first() ?? BusinessType::create([
            'code' => 'retail',
            'name' => 'Retail',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        return Business::create([
            'name' => $name,
            'owner_name' => 'Owner',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08123456789',
            'business_type_id' => $businessType->id,
            'trial_end_at' => now()->addDays(14),
        ]);
    }

    public function test_scenario_1_percentage_discount_with_max_cap(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Diskon 10% Max 20rb',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => 10.0,
            'max_discount_amount' => 20000.0,
            'min_subtotal' => 0.0,
            'min_quantity' => 1.0,
            'created_by' => $user->id,
        ]);

        // Cart 1: Rp 100.000 -> 10% = Rp 10.000
        $cart1 = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);
        $result1 = $this->evaluator->evaluate($cart1);
        $this->assertEquals(10000.0, $result1->totalDiscount);
        $this->assertEquals(90000.0, $result1->finalSubtotal);

        // Cart 2: Rp 300.000 -> 10% is Rp 30.000, but capped at Rp 20.000
        $cart2 = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_1', 'quantity' => 3, 'unit_price' => 100000, 'subtotal' => 300000],
            ],
        ]);
        $result2 = $this->evaluator->evaluate($cart2);
        $this->assertEquals(20000.0, $result2->totalDiscount);
        $this->assertEquals(280000.0, $result2->finalSubtotal);
    }

    public function test_scenario_2_fixed_discount_with_min_subtotal(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Potongan 25rb Min Belanja 150rb',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Fixed,
            'discount_value' => 25000.0,
            'min_subtotal' => 150000.0,
            'min_quantity' => 1.0,
            'created_by' => $user->id,
        ]);

        // Cart subtotal Rp 100.000 (Below minimum) -> Discount Rp 0
        $cartBelow = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);
        $resultBelow = $this->evaluator->evaluate($cartBelow);
        $this->assertEquals(0.0, $resultBelow->totalDiscount);
        $this->assertEquals(100000.0, $resultBelow->finalSubtotal);

        // Cart subtotal Rp 160.000 (Meets minimum) -> Discount Rp 25.000
        $cartMeets = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_1', 'quantity' => 2, 'unit_price' => 80000, 'subtotal' => 160000],
            ],
        ]);
        $resultMeets = $this->evaluator->evaluate($cartMeets);
        $this->assertEquals(25000.0, $resultMeets->totalDiscount);
        $this->assertEquals(135000.0, $resultMeets->finalSubtotal);
    }

    public function test_scenario_3_category_target_discount(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $categoryFood = ProductCategory::create([
            'business_id' => $business->id,
            'name' => 'Food',
            'created_by' => $user->id,
        ]);

        $categoryDrink = ProductCategory::create([
            'business_id' => $business->id,
            'name' => 'Drink',
            'created_by' => $user->id,
        ]);

        $promo = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Diskon Kategori Makanan 15%',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Category,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => 15.0,
            'max_discount_amount' => null,
            'created_by' => $user->id,
        ]);
        $promo->categories()->attach($categoryFood->id);

        // Cart with Food (Rp 50.000) and Drink (Rp 30.000) -> 15% of 50.000 = Rp 7.500
        $cart = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_food', 'category_id' => $categoryFood->id, 'quantity' => 1, 'unit_price' => 50000, 'subtotal' => 50000],
                ['id' => 'item_drink', 'category_id' => $categoryDrink->id, 'quantity' => 1, 'unit_price' => 30000, 'subtotal' => 30000],
            ],
        ]);

        $result = $this->evaluator->evaluate($cart);
        $this->assertEquals(7500.0, $result->totalDiscount);
        $this->assertEquals(72500.0, $result->finalSubtotal);
        $this->assertEquals(7500.0, $result->itemDiscounts['item_food']);
        $this->assertArrayNotHasKey('item_drink', $result->itemDiscounts);
    }

    public function test_scenario_4_product_variant_fixed_discount_per_item(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $product = Product::create([
            'business_id' => $business->id,
            'product_type' => ProductTypeEnum::BASIC->value,
            'name' => 'Kopi Susu',
        ]);

        $variant = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Susu Large',
            'sku' => 'KOP-SUS-LG',
        ]);

        $promo = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Diskon 5rb per Varian Kopi Susu Large',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Variant,
            'discount_type' => PromotionDiscountType::Fixed,
            'discount_value' => 5000.0,
            'created_by' => $user->id,
        ]);
        $promo->productItems()->attach($variant->id);

        // Cart with 2x Kopi Susu Large (unit price 20.000 = 40.000) and other item (10.000)
        $cart = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'line_variant', 'product_item_id' => $variant->id, 'quantity' => 2, 'unit_price' => 20000, 'subtotal' => 40000],
                ['id' => 'line_other', 'product_item_id' => 'other_id', 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000],
            ],
        ]);

        $result = $this->evaluator->evaluate($cart);
        // 2 items * Rp 5.000 = Rp 10.000 discount
        $this->assertEquals(10000.0, $result->totalDiscount);
        $this->assertEquals(40000.0, $result->finalSubtotal);
        $this->assertEquals(10000.0, $result->itemDiscounts['line_variant']);
    }

    public function test_scenario_5_wholesale_quantity_discount(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $product = Product::create([
            'business_id' => $business->id,
            'product_type' => ProductTypeEnum::BASIC->value,
            'name' => 'Kaos Polos',
        ]);

        $promo = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Diskon Grosir 20% Min 5 pcs',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Product,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => 20.0,
            'min_quantity' => 5.0,
            'created_by' => $user->id,
        ]);
        $promo->products()->attach($product->id);

        // Cart 1: 3 pcs (Threshold 5 not reached) -> Discount 0
        $cartBelow = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'line_1', 'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 50000, 'subtotal' => 150000],
            ],
        ]);
        $resultBelow = $this->evaluator->evaluate($cartBelow);
        $this->assertEquals(0.0, $resultBelow->totalDiscount);

        // Cart 2: 5 pcs (Threshold 5 reached) -> 20% of 250.000 = Rp 50.000
        $cartMeets = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'line_1', 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 50000, 'subtotal' => 250000],
            ],
        ]);
        $resultMeets = $this->evaluator->evaluate($cartMeets);
        $this->assertEquals(50000.0, $resultMeets->totalDiscount);
        $this->assertEquals(200000.0, $resultMeets->finalSubtotal);
    }

    public function test_scenario_6_manual_voucher_coupon_code(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Voucher Promo Manual HEMAT50',
            'application_mode' => PromotionApplicationMode::Manual,
            'promo_code' => 'HEMAT50',
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Fixed,
            'discount_value' => 50000.0,
            'min_subtotal' => 200000.0,
            'created_by' => $user->id,
        ]);

        $cartWithoutCode = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'items' => [
                ['id' => 'item_1', 'quantity' => 2, 'unit_price' => 100000, 'subtotal' => 200000],
            ],
        ]);
        $resultWithoutCode = $this->evaluator->evaluate($cartWithoutCode);
        $this->assertEquals(0.0, $resultWithoutCode->totalDiscount);

        $cartWrongCode = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'promo_code' => 'WRONGCODE',
            'items' => [
                ['id' => 'item_1', 'quantity' => 2, 'unit_price' => 100000, 'subtotal' => 200000],
            ],
        ]);
        $resultWrongCode = $this->evaluator->evaluate($cartWrongCode);
        $this->assertEquals(0.0, $resultWrongCode->totalDiscount);

        $cartValidCode = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'promo_code' => 'hemat50', // Case-insensitive matching
            'items' => [
                ['id' => 'item_1', 'quantity' => 2, 'unit_price' => 100000, 'subtotal' => 200000],
            ],
        ]);
        $resultValidCode = $this->evaluator->evaluate($cartValidCode);
        $this->assertEquals(50000.0, $resultValidCode->totalDiscount);
        $this->assertEquals(150000.0, $resultValidCode->finalSubtotal);
    }

    public function test_scenario_7_happy_hour_and_day_range_conditions(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        // Happy hour: 14:00 - 17:00 on Monday, Tuesday, Wednesday (Days 1, 2, 3)
        Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Happy Hour Siang 30%',
            'application_mode' => PromotionApplicationMode::Automatic,
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => 30.0,
            'start_time' => '14:00',
            'end_time' => '17:00',
            'days_of_week' => [1, 2, 3],
            'created_by' => $user->id,
        ]);

        // Monday (2026-10-05) at 15:00 -> Matches Happy Hour!
        $cartMatch = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'evaluated_at' => '2026-10-05 15:00:00',
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);
        $resultMatch = $this->evaluator->evaluate($cartMatch);
        $this->assertEquals(30000.0, $resultMatch->totalDiscount);

        // Monday (2026-10-05) at 18:00 (Outside Time Range) -> Discount 0
        $cartOutsideTime = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'evaluated_at' => '2026-10-05 18:00:00',
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);
        $resultOutsideTime = $this->evaluator->evaluate($cartOutsideTime);
        $this->assertEquals(0.0, $resultOutsideTime->totalDiscount);

        // Saturday (2026-10-10) at 15:00 (Outside Days Range) -> Discount 0
        $cartOutsideDay = CartEvaluationDTO::fromArray([
            'business_id' => $business->id,
            'evaluated_at' => '2026-10-10 15:00:00',
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);
        $resultOutsideDay = $this->evaluator->evaluate($cartOutsideDay);
        $this->assertEquals(0.0, $resultOutsideDay->totalDiscount);
    }

    public function test_scenario_8_tenant_isolation_assurance(): void
    {
        $businessA = $this->createBusiness('Business A');
        $businessB = $this->createBusiness('Business B');
        $userA = User::factory()->create(['business_id' => $businessA->id]);

        Promotion::factory()->active()->create([
            'business_id' => $businessA->id,
            'name' => 'Promo Rahasia Tenant A',
            'application_mode' => PromotionApplicationMode::Manual,
            'promo_code' => 'TENANTA100',
            'discount_type' => PromotionDiscountType::Fixed,
            'discount_value' => 50000.0,
            'created_by' => $userA->id,
        ]);

        // Cart evaluated by Business B using Business A's promo code
        $cartB = CartEvaluationDTO::fromArray([
            'business_id' => $businessB->id,
            'promo_code' => 'TENANTA100',
            'items' => [
                ['id' => 'item_1', 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);

        $resultB = $this->evaluator->evaluate($cartB);
        $this->assertEquals(0.0, $resultB->totalDiscount);
        $this->assertEmpty($resultB->appliedPromotions);
    }
}
