<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case Purchase = 'purchase';
    case PurchaseVoid = 'purchase_void';
    case PurchaseReturn = 'purchase_return';
    case Adjustment = 'adjustment';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case RecipeDeduction = 'recipe_deduction';
    case RecipeReturn = 'recipe_return';
    case BundleDeduction = 'bundle_deduction';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Waste = 'waste';
    case Opname = 'opname';
    case OpnameSurplus = 'opname_surplus';
    case OpnameDeficit = 'opname_deficit';
    case InitialStock = 'initial_stock';

    /**
     * Get human-readable label in Indonesian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Penjualan',
            self::SaleReturn => 'Retur/Void Penjualan',
            self::Purchase => 'Pembelian',
            self::PurchaseVoid => 'Void Pembelian',
            self::PurchaseReturn => 'Retur Pembelian',
            self::Adjustment => 'Penyesuaian Stok',
            self::AdjustmentIn => 'Penyesuaian Masuk',
            self::AdjustmentOut => 'Penyesuaian Keluar',
            self::RecipeDeduction => 'Pemakaian Resep',
            self::RecipeReturn => 'Pembatalan Resep',
            self::BundleDeduction => 'Deduksi Bundle',
            self::TransferIn => 'Transfer Masuk',
            self::TransferOut => 'Transfer Keluar',
            self::Waste => 'Kerusakan / Kedaluwarsa (Waste)',
            self::Opname => 'Penyesuaian Opname',
            self::OpnameSurplus => 'Selisih Lebih Opname',
            self::OpnameDeficit => 'Selisih Kurang Opname',
            self::InitialStock => 'Saldo Awal Stok',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
