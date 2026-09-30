<?php

namespace App\Services\App\Promotion;

use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PromotionService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function create(array $data, ?object $causer = null): Promotion
    {
        return DB::transaction(function () use ($data, $causer) {
            /** @var Promotion $promotion */
            $promotion = Promotion::create(array_merge($data, [
                'status' => PromotionStatus::Draft->value,
                'created_by' => $causer?->id ?? auth()->id(),
            ]));

            $this->syncRelations($promotion, $data);

            $this->activityLogService->log($promotion, 'created', $causer);

            return $promotion;
        });
    }

    public function update(Promotion $promotion, array $data, ?object $causer = null): Promotion
    {
        if ($promotion->status === PromotionStatus::Active) {
            throw new InvalidArgumentException('Promo yang sedang aktif tidak dapat diubah langsung. Nonaktifkan promo terlebih dahulu.');
        }

        return DB::transaction(function () use ($promotion, $data, $causer) {
            $promotion->update($data);

            $this->syncRelations($promotion, $data);

            $this->activityLogService->log($promotion, 'updated', $causer);

            return $promotion;
        });
    }

    public function delete(Promotion $promotion, ?object $causer = null): void
    {
        if ($promotion->status !== PromotionStatus::Draft) {
            throw new InvalidArgumentException('Hanya promo berstatus Draf yang dapat dihapus.');
        }

        DB::transaction(function () use ($promotion, $causer) {
            $this->activityLogService->log($promotion, 'deleted', $causer);
            $promotion->delete();
        });
    }

    public function publish(Promotion $promotion, ?object $causer = null): Promotion
    {
        if ($promotion->end_date->isPast() && ! $promotion->end_date->isToday()) {
            throw new InvalidArgumentException('Tanggal berakhir promo sudah terlewat.');
        }

        $promotion->update([
            'status' => PromotionStatus::Active->value,
            'published_by' => $causer?->id ?? auth()->id(),
            'published_at' => now(),
        ]);

        $this->activityLogService->log($promotion, 'published', $causer);

        return $promotion;
    }

    public function unpublish(Promotion $promotion, ?object $causer = null): Promotion
    {
        if ($promotion->status !== PromotionStatus::Active) {
            throw new InvalidArgumentException('Hanya promo aktif yang dapat dinonaktifkan.');
        }

        $promotion->update([
            'status' => PromotionStatus::Inactive->value,
        ]);

        $this->activityLogService->log($promotion, 'unpublished', $causer);

        return $promotion;
    }

    public function syncRelations(Promotion $promotion, array $data): void
    {
        // 1. Sync Outlets
        $appliesToAll = isset($data['applies_to_all_outlets']) ? (bool) $data['applies_to_all_outlets'] : $promotion->applies_to_all_outlets;
        if (! $appliesToAll) {
            if (isset($data['outlet_ids']) && is_array($data['outlet_ids'])) {
                $validOutletIds = Outlet::query()
                    ->where('business_id', $promotion->business_id)
                    ->whereIn('id', $data['outlet_ids'])
                    ->pluck('id')
                    ->all();
                $promotion->outlets()->sync($validOutletIds);
            }
        } else {
            $promotion->outlets()->detach();
        }

        // 2. Sync Targets
        $targetScope = $data['target_scope'] ?? $promotion->target_scope;
        $targetScopeValue = $targetScope instanceof PromotionTargetScope ? $targetScope->value : (string) $targetScope;

        // Categories
        if ($targetScopeValue === PromotionTargetScope::Category->value) {
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $validCategoryIds = ProductCategory::query()
                    ->where('business_id', $promotion->business_id)
                    ->whereIn('id', $data['category_ids'])
                    ->pluck('id')
                    ->all();
                $promotion->categories()->sync($validCategoryIds);
            }
        } else {
            $promotion->categories()->detach();
        }

        // Products
        if ($targetScopeValue === PromotionTargetScope::Product->value) {
            if (isset($data['product_ids']) && is_array($data['product_ids'])) {
                $validProductIds = Product::query()
                    ->where('business_id', $promotion->business_id)
                    ->whereIn('id', $data['product_ids'])
                    ->pluck('id')
                    ->all();
                $promotion->products()->sync($validProductIds);
            }
        } else {
            $promotion->products()->detach();
        }

        // Variants / Product Items
        if ($targetScopeValue === PromotionTargetScope::Variant->value) {
            if (isset($data['product_item_ids']) && is_array($data['product_item_ids'])) {
                $validItemIds = ProductItem::query()
                    ->where('business_id', $promotion->business_id)
                    ->whereIn('id', $data['product_item_ids'])
                    ->pluck('id')
                    ->all();
                $promotion->productItems()->sync($validItemIds);
            }
        } else {
            $promotion->productItems()->detach();
        }
    }
}
