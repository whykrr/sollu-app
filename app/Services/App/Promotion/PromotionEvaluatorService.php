<?php

namespace App\Services\App\Promotion;

use App\DTOs\Promotion\AppliedPromotionDTO;
use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\CartItemDTO;
use App\DTOs\Promotion\DiscountEvaluationResultDTO;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Promotion\Promotion;
use App\Services\App\Promotion\Contracts\PromotionEvaluatorInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PromotionEvaluatorService implements PromotionEvaluatorInterface
{
    /**
     * Mengevaluasi seluruh promo aktif terhadap keranjang belanja tanpa mutasi database.
     */
    public function evaluate(CartEvaluationDTO $cart): DiscountEvaluationResultDTO
    {
        $originalSubtotal = (float) ($cart->subtotal ?? 0.0);
        if ($originalSubtotal <= 0 && ! empty($cart->items)) {
            $originalSubtotal = (float) array_sum(array_map(fn (CartItemDTO $item) => $item->subtotal, $cart->items));
        }

        if ($originalSubtotal <= 0 || empty($cart->items)) {
            return new DiscountEvaluationResultDTO(
                originalSubtotal: $originalSubtotal,
                totalDiscount: 0.0,
                finalSubtotal: $originalSubtotal,
                appliedPromotions: [],
                itemDiscounts: [],
            );
        }

        $evaluatedAt = $cart->evaluatedAt ?? now();
        $cartDate = $evaluatedAt->toDateString();

        // 1. Ambil seluruh kandidat promo aktif untuk tenant & outlet yang bersangkutan
        $activePromotions = Promotion::query()
            ->where('business_id', $cart->businessId)
            ->where('status', PromotionStatus::Active->value)
            ->whereDate('start_date', '<=', $cartDate)
            ->whereDate('end_date', '>=', $cartDate)
            ->where(function (Builder $query) use ($cart) {
                $query->where('applies_to_all_outlets', true);
                if (! empty($cart->outletId)) {
                    $query->orWhereHas('outlets', fn (Builder $q) => $q->where('outlets.id', $cart->outletId));
                }
            })
            ->with(['categories:id', 'products:id', 'productItems:id'])
            ->get();

        $appliedPromotions = [];
        $itemDiscounts = [];
        $remainingSubtotal = $originalSubtotal;

        foreach ($activePromotions as $promotion) {
            // Evaluasi filter multidimensi waktu & jadwal
            if (! $this->isScheduleMatching($promotion, $evaluatedAt)) {
                continue;
            }

            // Evaluasi filter trigger mode (Automatic vs Promo Code)
            if (! $this->isTriggerMatching($promotion, $cart->promoCode)) {
                continue;
            }

            // Filter item keranjang yang cocok dengan Target Scope
            $matchingItems = $this->getMatchingCartItems($promotion, $cart->items);
            if (empty($matchingItems)) {
                continue;
            }

            // Evaluasi Threshold Conditions (Min Subtotal & Min Quantity)
            $matchingSubtotal = (float) array_sum(array_map(fn (CartItemDTO $item) => $item->subtotal, $matchingItems));
            $matchingQuantity = (float) array_sum(array_map(fn (CartItemDTO $item) => $item->quantity, $matchingItems));

            if ($promotion->min_subtotal > 0 && $matchingSubtotal < $promotion->min_subtotal) {
                continue;
            }

            if ($promotion->min_quantity > 1 && $matchingQuantity < $promotion->min_quantity) {
                continue;
            }

            // Hitung benefit diskon
            $discountAmount = $this->calculateDiscountAmount($promotion, $matchingSubtotal, $matchingQuantity);
            if ($discountAmount <= 0) {
                continue;
            }

            // Pastikan diskon tidak melebihi sisa subtotal
            $discountAmount = min($discountAmount, $remainingSubtotal, $matchingSubtotal);

            // Alokasikan diskon per baris item (Hanya untuk promosi ber-scope item/varian/kategori)
            $affectedItemIds = [];
            if ($promotion->target_scope !== PromotionTargetScope::Transaction) {
                foreach ($matchingItems as $item) {
                    $affectedItemIds[] = $item->id;
                    $itemProportion = $matchingSubtotal > 0 ? ($item->subtotal / $matchingSubtotal) : 0;
                    $lineDiscount = round($discountAmount * $itemProportion, 4);

                    $itemDiscounts[$item->id] = ($itemDiscounts[$item->id] ?? 0.0) + $lineDiscount;
                }
            } else {
                $affectedItemIds = array_map(fn (CartItemDTO $item) => $item->id, $matchingItems);
            }

            $appliedPromotions[] = new AppliedPromotionDTO(
                promotionId: (string) $promotion->id,
                promotionName: (string) $promotion->name,
                promoCode: $promotion->promo_code,
                discountType: $promotion->discount_type instanceof PromotionDiscountType ? $promotion->discount_type->value : (string) $promotion->discount_type,
                discountValue: (float) $promotion->discount_value,
                discountAmount: (float) $discountAmount,
                allocationLevel: $promotion->target_scope === PromotionTargetScope::Transaction ? 'transaction' : 'item',
                targetScope: $promotion->target_scope instanceof PromotionTargetScope ? $promotion->target_scope->value : (string) $promotion->target_scope,
                affectedItemIds: $affectedItemIds,
            );

            $remainingSubtotal -= $discountAmount;
            if ($remainingSubtotal <= 0) {
                break;
            }
        }

        $totalDiscount = (float) array_sum(array_map(fn (AppliedPromotionDTO $p) => $p->discountAmount, $appliedPromotions));
        $totalDiscount = min($totalDiscount, $originalSubtotal);
        $finalSubtotal = max(0.0, $originalSubtotal - $totalDiscount);

        return new DiscountEvaluationResultDTO(
            originalSubtotal: $originalSubtotal,
            totalDiscount: $totalDiscount,
            finalSubtotal: $finalSubtotal,
            appliedPromotions: $appliedPromotions,
            itemDiscounts: $itemDiscounts,
        );
    }

    /**
     * Cek kesesuaian jam operasional (Happy Hour) dan hari berlaku.
     */
    protected function isScheduleMatching(Promotion $promotion, Carbon $evaluatedAt): bool
    {
        // 1. Cek jam operasional (Time Range)
        if (! empty($promotion->start_time) && ! empty($promotion->end_time)) {
            $currentTime = $evaluatedAt->format('H:i');
            $startTime = $promotion->start_time instanceof Carbon ? $promotion->start_time->format('H:i') : substr((string) $promotion->start_time, 0, 5);
            $endTime = $promotion->end_time instanceof Carbon ? $promotion->end_time->format('H:i') : substr((string) $promotion->end_time, 0, 5);

            if ($currentTime < $startTime || $currentTime > $endTime) {
                return false;
            }
        }

        // 2. Cek hari berlaku (1 = Senin, 7 = Minggu)
        if (! empty($promotion->days_of_week) && is_array($promotion->days_of_week)) {
            $currentDayIso = $evaluatedAt->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
            $allowedDays = array_map('intval', $promotion->days_of_week);

            if (! in_array($currentDayIso, $allowedDays, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cek kesesuaian trigger mode (Automatic vs Manual Voucher Code).
     */
    protected function isTriggerMatching(Promotion $promotion, ?string $cartPromoCode): bool
    {
        if ($promotion->application_mode === PromotionApplicationMode::Automatic) {
            return true;
        }

        if ($promotion->application_mode === PromotionApplicationMode::Manual) {
            if (empty($cartPromoCode) || empty($promotion->promo_code)) {
                return false;
            }

            return strcasecmp(trim($cartPromoCode), trim($promotion->promo_code)) === 0;
        }

        return false;
    }

    /**
     * Filter baris item pada keranjang yang memenuhi target scope promosi.
     *
     * @param  array<CartItemDTO>  $items
     * @return array<CartItemDTO>
     */
    protected function getMatchingCartItems(Promotion $promotion, array $items): array
    {
        if ($promotion->target_scope === PromotionTargetScope::Transaction) {
            return $items;
        }

        $matchingItems = [];

        if ($promotion->target_scope === PromotionTargetScope::Category) {
            $categoryIds = $promotion->categories->pluck('id')->all();
            foreach ($items as $item) {
                if ($item->categoryId && in_array($item->categoryId, $categoryIds, true)) {
                    $matchingItems[] = $item;
                }
            }
        } elseif ($promotion->target_scope === PromotionTargetScope::Product) {
            $productIds = $promotion->products->pluck('id')->all();
            foreach ($items as $item) {
                if ($item->productId && in_array($item->productId, $productIds, true)) {
                    $matchingItems[] = $item;
                }
            }
        } elseif ($promotion->target_scope === PromotionTargetScope::Variant) {
            $variantIds = $promotion->productItems->pluck('id')->all();
            foreach ($items as $item) {
                if ($item->productItemId && in_array($item->productItemId, $variantIds, true)) {
                    $matchingItems[] = $item;
                }
            }
        }

        return $matchingItems;
    }

    /**
     * Hitung nilai nominal potongan diskon berdasarkan tipe dan batas cap.
     */
    protected function calculateDiscountAmount(Promotion $promotion, float $matchingSubtotal, float $matchingQuantity): float
    {
        $discountAmount = 0.0;

        if ($promotion->discount_type === PromotionDiscountType::Percentage) {
            $discountAmount = $matchingSubtotal * ($promotion->discount_value / 100.0);

            if ($promotion->max_discount_amount !== null && $promotion->max_discount_amount > 0) {
                $discountAmount = min($discountAmount, (float) $promotion->max_discount_amount);
            }
        } elseif ($promotion->discount_type === PromotionDiscountType::Fixed) {
            if ($promotion->target_scope === PromotionTargetScope::Transaction) {
                $discountAmount = min($promotion->discount_value, $matchingSubtotal);
            } else {
                // Untuk target produk / varian / kategori, diskon fixed per item
                $discountAmount = min($promotion->discount_value * $matchingQuantity, $matchingSubtotal);
            }
        }

        return round($discountAmount, 4);
    }
}
