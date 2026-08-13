<?php

namespace App\Enums;

enum PromoTarget: string
{
    case Product = 'product';
    case Bill = 'bill';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Per Produk',
            self::Bill => 'Per Bill',
        };
    }
}
