<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesChannelEnum: string
{
    case Wholesale = 'wholesale';
    case Direct = 'direct';
    case ECommerce = 'e_commerce';
    case SocialMedia = 'social_media';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Wholesale => 'Grosir (Wholesale)',
            self::Direct => 'Penjualan Langsung (Direct)',
            self::ECommerce => 'E-Commerce / Marketplace',
            self::SocialMedia => 'Media Sosial & WhatsApp',
            self::Custom => 'Pesanan Khusus',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Wholesale => 'purple',
            self::Direct => 'blue',
            self::ECommerce => 'emerald',
            self::SocialMedia => 'amber',
            self::Custom => 'cyan',
        };
    }
}
