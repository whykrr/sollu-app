<?php

namespace App\Services;

use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Models\Promo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PromoService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function create(array $data, ?object $causer = null): Promo
    {
        return DB::transaction(function () use ($data, $causer) {
            $promo = Promo::create(array_merge($data, [
                'status' => PromoStatus::Draft->value,
                'created_by' => $causer?->id ?? auth()->id(),
            ]));

            $this->syncRelations($promo, $data);

            $this->activityLogService->log($promo, 'created', $causer);

            return $promo;
        });
    }

    public function update(Promo $promo, array $data, ?object $causer = null): Promo
    {
        if ($promo->status !== PromoStatus::Draft) {
            throw new InvalidArgumentException('Hanya promo berstatus Draf yang dapat diubah.');
        }

        return DB::transaction(function () use ($promo, $data, $causer) {
            $promo->update($data);

            $this->syncRelations($promo, $data);

            $this->activityLogService->log($promo, 'updated', $causer);

            return $promo;
        });
    }

    public function delete(Promo $promo, ?object $causer = null): void
    {
        if ($promo->status === PromoStatus::Active) {
            throw new InvalidArgumentException('Promo yang sudah aktif tidak dapat dihapus.');
        }

        DB::transaction(function () use ($promo, $causer) {
            $this->activityLogService->log($promo, 'deleted', $causer);
            $promo->delete();
        });
    }

    public function publish(Promo $promo, ?object $causer = null): Promo
    {
        if ($promo->end_date->isPast()) {
            throw new InvalidArgumentException('Tanggal berakhir promo sudah terlewat.');
        }

        $promo->update([
            'status' => PromoStatus::Active->value,
            'published_by' => $causer?->id ?? auth()->id(),
            'published_at' => now(),
        ]);

        $this->activityLogService->log($promo, 'published', $causer);

        return $promo;
    }

    public function unpublish(Promo $promo, ?object $causer = null): Promo
    {
        if ($promo->status !== PromoStatus::Active) {
            throw new InvalidArgumentException('Hanya promo aktif yang dapat dinonaktifkan.');
        }

        $promo->update([
            'status' => PromoStatus::Inactive->value,
        ]);

        $this->activityLogService->log($promo, 'unpublished', $causer);

        return $promo;
    }

    protected function syncRelations(Promo $promo, array $data): void
    {
        if (isset($data['applies_to_all_outlets']) && ! $data['applies_to_all_outlets']) {
            if (isset($data['outlet_ids']) && is_array($data['outlet_ids'])) {
                $promo->outlets()->sync($data['outlet_ids']);
            }
        } else {
            $promo->outlets()->detach();
        }

        if (isset($data['target_type']) && $data['target_type'] === PromoTarget::Product->value) {
            if (isset($data['inventory_item_ids']) && is_array($data['inventory_item_ids'])) {
                $promo->inventoryItems()->sync($data['inventory_item_ids']);
            }
        } else {
            $promo->inventoryItems()->detach();
        }
    }
}
