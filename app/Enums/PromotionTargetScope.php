<?php

namespace App\Enums;

enum PromotionTargetScope: string
{
    case Transaction = 'transaction';
    case Category = 'category';
    case Product = 'product';
    case Variant = 'variant';

    public function label(): string
    {
        return match ($this) {
            self::Transaction => 'Seluruh Transaksi',
            self::Category => 'Kategori Produk',
            self::Product => 'Produk Spesifik',
            self::Variant => 'Varian Produk (SKU)',
        };
    }
}
