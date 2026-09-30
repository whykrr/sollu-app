<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\CartItemDTO;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Http\Controllers\Controller;
use App\Http\Resources\PromoResource;
use App\Models\Promotion\Promotion;
use App\Services\App\Promotion\Contracts\PromotionEvaluatorInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function __construct(
        protected ?PromotionEvaluatorInterface $promotionEvaluator = null
    ) {
        $this->promotionEvaluator = $promotionEvaluator ?? app(PromotionEvaluatorInterface::class);
    }

    /**
     * Search active promos for dropdown / select2 API.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string',
            'search' => 'nullable|string',
            'outlet_id' => 'nullable|uuid',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->integer('limit', 20);
        $outletId = $request->query('outlet_id');
        $searchTerm = $request->query('query') ?: $request->query('search');

        $promotions = Promotion::currentBusiness()
            ->active()
            ->when($outletId, function ($q, $outletId) {
                $q->where(function ($sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn ($o) => $o->where('outlets.id', $outletId));
                });
            })
            ->when($searchTerm, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereLike('name', "%{$search}%")
                        ->orWhereLike('promo_code', "%{$search}%");
                });
            })
            ->with('productItems:id')
            ->take($limit)
            ->get();

        return $this->successResponse(PromoResource::collection($promotions));
    }

    /**
     * Get available active promotions for an outlet (especially for Transaction Promo modal).
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => 'required|uuid',
            'target_scope' => 'nullable|string|in:transaction,product,category,variant',
        ]);

        $businessId = $request->user()->business_id;
        $outletId = $request->query('outlet_id');
        $targetScope = $request->query('target_scope');
        $today = now()->toDateString();

        $promotions = Promotion::query()
            ->where('business_id', $businessId)
            ->where('status', PromotionStatus::Active->value)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function (Builder $query) use ($outletId) {
                $query->where('applies_to_all_outlets', true);
                if (! empty($outletId)) {
                    $query->orWhereHas('outlets', fn (Builder $q) => $q->where('outlets.id', $outletId));
                }
            })
            ->when($targetScope, fn ($q) => $q->where('target_scope', $targetScope))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $promotions->map(fn (Promotion $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'application_mode' => $p->application_mode instanceof PromotionApplicationMode ? $p->application_mode->value : (string) $p->application_mode,
                'promo_code' => $p->promo_code,
                'target_scope' => $p->target_scope instanceof PromotionTargetScope ? $p->target_scope->value : (string) $p->target_scope,
                'discount_type' => $p->discount_type instanceof PromotionDiscountType ? $p->discount_type->value : (string) $p->discount_type,
                'discount_value' => (float) $p->discount_value,
                'max_discount_amount' => $p->max_discount_amount ? (float) $p->max_discount_amount : null,
                'min_subtotal' => (float) ($p->min_subtotal ?? 0),
                'min_quantity' => (float) ($p->min_quantity ?? 1),
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'days_of_week' => $p->days_of_week,
            ]),
        ]);
    }

    /**
     * Evaluate cart items against promotions in real-time without database mutation.
     */
    public function evaluate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id' => 'required|uuid',
            'channel' => 'nullable|string|in:wholesale,direct',
            'promo_code' => 'nullable|string|max:50',
            'evaluated_at' => 'nullable|date',
            'items' => 'required|array',
            'items.*.id' => 'nullable|string',
            'items.*.product_id' => 'nullable|uuid',
            'items.*.product_item_id' => 'nullable|uuid',
            'items.*.inventory_item_id' => 'nullable|uuid',
            'items.*.category_id' => 'nullable|uuid',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $businessId = $request->user()->business_id;
        $channel = $validated['channel'] ?? 'wholesale';
        $promoCode = ! empty($validated['promo_code']) ? trim($validated['promo_code']) : null;
        $evaluatedAt = ! empty($validated['evaluated_at']) ? Carbon::parse($validated['evaluated_at']) : now();

        $cartItems = [];
        $subtotal = 0.0;
        foreach ($validated['items'] as $idx => $item) {
            $itemId = (string) ($item['id'] ?? 'item_'.$idx);
            $qty = (float) $item['qty'];
            $price = (float) $item['price'];
            $itemDiscount = (float) ($item['discount_amount'] ?? 0.0);
            $itemSubtotal = max(0.0, ($qty * $price) - $itemDiscount);
            $subtotal += $itemSubtotal;

            $cartItems[] = new CartItemDTO(
                id: $itemId,
                productId: $item['product_id'] ?? null,
                productItemId: $item['product_item_id'] ?? $item['inventory_item_id'] ?? $item['product_id'] ?? null,
                categoryId: $item['category_id'] ?? null,
                quantity: $qty,
                unitPrice: $price,
                subtotal: $itemSubtotal,
            );
        }

        $cartDto = new CartEvaluationDTO(
            businessId: $businessId,
            outletId: $validated['outlet_id'],
            channel: $channel,
            items: $cartItems,
            evaluatedAt: $evaluatedAt,
            subtotal: $subtotal,
            promoCode: $promoCode,
        );

        $result = $this->promotionEvaluator->evaluate($cartDto);

        return response()->json([
            'data' => [
                'original_subtotal' => $result->originalSubtotal,
                'total_discount' => $result->totalDiscount,
                'final_subtotal' => $result->finalSubtotal,
                'applied_promotions' => array_map(fn ($p) => [
                    'promotion_id' => $p->promotionId,
                    'promotion_name' => $p->promotionName,
                    'promo_code' => $p->promoCode,
                    'discount_type' => $p->discountType,
                    'discount_value' => $p->discountValue,
                    'discount_amount' => $p->discountAmount,
                    'allocation_level' => $p->allocationLevel,
                    'target_scope' => $p->targetScope,
                    'affected_item_ids' => $p->affectedItemIds,
                ], $result->appliedPromotions),
                'item_discounts' => $result->itemDiscounts,
            ],
        ]);
    }
}
