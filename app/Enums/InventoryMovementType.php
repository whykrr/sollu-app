<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Sale             = 'sale';
    case Purchase         = 'purchase';
    case Adjustment       = 'adjustment';
    case RecipeDeduction  = 'recipe_deduction';
    case BundleDeduction  = 'bundle_deduction';
    case TransferIn       = 'transfer_in';
    case TransferOut      = 'transfer_out';
    case Waste            = 'waste';
    case Opname           = 'opname';

    /**
     * Get human-readable label in Indonesian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sale            => 'Penjualan',
            self::Purchase        => 'Pembelian',
            self::Adjustment      => 'Penyesuaian',
            self::RecipeDeduction => 'Deduksi Resep',
            self::BundleDeduction => 'Deduksi Bundle',
            self::TransferIn      => 'Transfer Masuk',
            self::TransferOut     => 'Transfer Keluar',
            self::Waste           => 'Waste',
            self::Opname          => 'Opname',
        };
    }
}
