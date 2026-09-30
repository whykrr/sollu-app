<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Promotion\AppliedPromotionDTO;
use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\CartItemDTO;
use App\DTOs\Promotion\DiscountEvaluationResultDTO;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PromotionDTOTest extends TestCase
{
    public function test_cart_item_dto_instantiation_and_serialization(): void
    {
        $dto = CartItemDTO::fromArray([
            'id' => 'item_1',
            'product_id' => 'prod_123',
            'product_item_id' => 'var_456',
            'category_id' => 'cat_789',
            'quantity' => 3,
            'unit_price' => 25000,
        ]);

        $this->assertEquals('item_1', $dto->id);
        $this->assertEquals('prod_123', $dto->productId);
        $this->assertEquals('var_456', $dto->productItemId);
        $this->assertEquals('cat_789', $dto->categoryId);
        $this->assertEquals(3.0, $dto->quantity);
        $this->assertEquals(25000.0, $dto->unitPrice);
        $this->assertEquals(75000.0, $dto->subtotal);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertEquals(75000.0, $array['subtotal']);
    }

    public function test_cart_evaluation_dto_instantiation_and_subtotal_calculation(): void
    {
        $item1 = CartItemDTO::fromArray([
            'id' => 'item_1',
            'product_id' => 'prod_1',
            'product_item_id' => 'var_1',
            'quantity' => 2,
            'unit_price' => 10000,
        ]);

        $item2 = CartItemDTO::fromArray([
            'id' => 'item_2',
            'product_id' => 'prod_2',
            'product_item_id' => 'var_2',
            'quantity' => 1,
            'unit_price' => 30000,
        ]);

        $cart = CartEvaluationDTO::fromArray([
            'business_id' => 'biz_123',
            'outlet_id' => 'out_456',
            'promo_code' => 'HEMAT20',
            'items' => [$item1, $item2],
            'evaluated_at' => '2026-09-30 14:00:00',
        ]);

        $this->assertEquals('biz_123', $cart->businessId);
        $this->assertEquals('out_456', $cart->outletId);
        $this->assertEquals('HEMAT20', $cart->promoCode);
        $this->assertCount(2, $cart->items);
        $this->assertEquals(50000.0, $cart->subtotal);
        $this->assertInstanceOf(Carbon::class, $cart->evaluatedAt);
    }

    public function test_applied_promotion_dto_and_discount_result_dto(): void
    {
        $appliedPromo = AppliedPromotionDTO::fromArray([
            'promotion_id' => 'promo_99',
            'promotion_name' => 'Diskon Merdeka 10%',
            'promo_code' => 'MERDEKA',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_amount' => 5000,
            'allocation_level' => 'transaction',
            'target_scope' => 'transaction',
            'affected_item_ids' => ['item_1', 'item_2'],
        ]);

        $this->assertEquals('promo_99', $appliedPromo->promotionId);
        $this->assertEquals(5000.0, $appliedPromo->discountAmount);

        $result = DiscountEvaluationResultDTO::fromArray([
            'original_subtotal' => 50000,
            'total_discount' => 5000,
            'final_subtotal' => 45000,
            'applied_promotions' => [$appliedPromo],
            'item_discounts' => ['item_1' => 2000, 'item_2' => 3000],
        ]);

        $this->assertEquals(50000.0, $result->originalSubtotal);
        $this->assertEquals(5000.0, $result->totalDiscount);
        $this->assertEquals(45000.0, $result->finalSubtotal);
        $this->assertCount(1, $result->appliedPromotions);
        $this->assertEquals(2000.0, $result->itemDiscounts['item_1']);
    }
}
