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
            self::InitialStock => 'Stok Awal',
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
            self::Waste => 'Kerusakan / Waste',
            self::Opname => 'Penyesuaian Opname',
            self::OpnameSurplus => 'Selisih Lebih Opname',
            self::OpnameDeficit => 'Selisih Kurang Opname',
        };
    }

    /**
     * Get badge color CSS class.
     */
    public function color(): string
    {
        return match ($this) {
            self::InitialStock => 'badge-main',
            self::Purchase, self::TransferIn, self::AdjustmentIn, self::OpnameSurplus, self::RecipeReturn, self::SaleReturn => 'badge-success',
            self::Sale, self::RecipeDeduction, self::BundleDeduction => 'badge-info',
            self::TransferOut, self::PurchaseReturn => 'badge-warning',
            self::Waste, self::OpnameDeficit, self::PurchaseVoid, self::AdjustmentOut => 'badge-danger',
            self::Adjustment, self::Opname => 'badge-gray',
        };
    }

    /**
     * Group key for categorization.
     */
    public function group(): string
    {
        return match ($this) {
            self::InitialStock => 'initial',
            self::Purchase, self::PurchaseVoid, self::PurchaseReturn => 'purchase',
            self::Sale, self::SaleReturn, self::RecipeDeduction, self::RecipeReturn, self::BundleDeduction => 'sales',
            self::Adjustment, self::AdjustmentIn, self::AdjustmentOut, self::Opname, self::OpnameSurplus, self::OpnameDeficit, self::Waste => 'adjustment',
            self::TransferIn, self::TransferOut => 'transfer',
        };
    }

    /**
     * Group label for categorization in UI.
     */
    public function groupLabel(): string
    {
        return match ($this->group()) {
            'initial' => 'Stok Awal',
            'purchase' => 'Pembelian & Pengadaan',
            'sales' => 'Penjualan & Resep',
            'adjustment' => 'Penyesuaian & Opname',
            'transfer' => 'Transfer Stok',
            default => 'Lainnya',
        };
    }

    /**
     * Determine if movement generally adds to inventory stock.
     */
    public function isIncrease(): bool
    {
        return match ($this) {
            self::InitialStock,
            self::Purchase,
            self::TransferIn,
            self::AdjustmentIn,
            self::OpnameSurplus,
            self::RecipeReturn,
            self::SaleReturn => true,
            default => false,
        };
    }

    /**
     * Determine if movement generally reduces inventory stock.
     */
    public function isDecrease(): bool
    {
        return match ($this) {
            self::Sale,
            self::RecipeDeduction,
            self::BundleDeduction,
            self::TransferOut,
            self::Waste,
            self::OpnameDeficit,
            self::PurchaseVoid,
            self::PurchaseReturn,
            self::AdjustmentOut => true,
            default => false,
        };
    }

    /**
     * Get grouped array of options for selection UI.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function grouped(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $groupName = $case->groupLabel();
            $result[$groupName][] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $result;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
