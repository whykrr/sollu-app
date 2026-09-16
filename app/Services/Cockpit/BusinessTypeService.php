<?php

namespace App\Services\Cockpit;

use App\Models\BusinessType;
use DomainException;
use Illuminate\Support\Facades\DB;

class BusinessTypeService
{
    /**
     * Create a new business type.
     */
    public function create(array $data): BusinessType
    {
        return DB::transaction(function () use ($data) {
            $businessType = BusinessType::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_visible' => (bool) ($data['is_visible'] ?? true),
                'features' => $data['features'] ?? [],
            ]);

            BusinessType::clearCache();

            return $businessType;
        });
    }

    /**
     * Update basic business type attributes.
     */
    public function update(BusinessType $businessType, array $data): BusinessType
    {
        return DB::transaction(function () use ($businessType, $data) {
            $attributes = [
                'code' => $data['code'],
                'name' => $data['name'],
                'sort_order' => (int) ($data['sort_order'] ?? $businessType->sort_order),
                'is_visible' => (bool) ($data['is_visible'] ?? $businessType->is_visible),
            ];

            if (array_key_exists('features', $data)) {
                $attributes['features'] = $data['features'];
            }

            $businessType->update($attributes);
            BusinessType::clearCache();

            return $businessType;
        });
    }

    /**
     * Toggle visibility status of a business type.
     */
    public function toggleVisibility(BusinessType $businessType): BusinessType
    {
        $businessType->update([
            'is_visible' => ! $businessType->is_visible,
        ]);

        BusinessType::clearCache();

        return $businessType;
    }

    /**
     * Update personalized default system features for this business type.
     *
     * @param  array<string>  $features
     */
    public function updateFeatures(BusinessType $businessType, array $features): BusinessType
    {
        return DB::transaction(function () use ($businessType, $features) {
            $businessType->update([
                'features' => array_values(array_unique($features)),
            ]);

            BusinessType::clearCache();

            return $businessType;
        });
    }

    /**
     * Delete business type if no merchant businesses are associated with it.
     *
     * @throws DomainException
     */
    public function delete(BusinessType $businessType): bool
    {
        if ($businessType->businesses()->exists()) {
            throw new DomainException(
                "Jenis bisnis {$businessType->name} tidak dapat dihapus karena masih digunakan oleh merchant aktif."
            );
        }

        return DB::transaction(function () use ($businessType) {
            $deleted = (bool) $businessType->delete();
            BusinessType::clearCache();

            return $deleted;
        });
    }
}
