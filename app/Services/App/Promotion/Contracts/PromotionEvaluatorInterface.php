<?php

namespace App\Services\App\Promotion\Contracts;

use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\DiscountEvaluationResultDTO;

interface PromotionEvaluatorInterface
{
    /**
     * Mengevaluasi seluruh promo aktif terhadap keranjang belanja tanpa mutasi database.
     */
    public function evaluate(CartEvaluationDTO $cart): DiscountEvaluationResultDTO;
}
