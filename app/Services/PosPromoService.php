<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Support\Collection;

class PosPromoService
{
    /**
     * Get applicable promos for a given outlet and datetime.
     * This will be called by CartService.
     */
    public function getApplicablePromos(string $outletId, \Carbon\Carbon $datetime): Collection
    {
        // TODO: Phase 2 - Evaluasi diskon berdasarkan outlet dan waktu.
        // Return daftar promo yang aktif dan berlaku.
        return collect();
    }

    /**
     * Apply manual discount to transaction or item
     */
    public function applyManualDiscount(string $type, float $value, float $originalAmount, ?float $maxDiscount = null): float
    {
        $discountAmount = 0;

        if ($type === 'percentage') {
            $discountAmount = $originalAmount * ($value / 100);
            if ($maxDiscount !== null && $discountAmount > $maxDiscount) {
                $discountAmount = $maxDiscount;
            }
        } elseif ($type === 'fixed') {
            $discountAmount = $value;
        }

        return min($discountAmount, $originalAmount); // Cannot discount more than original amount
    }
}
