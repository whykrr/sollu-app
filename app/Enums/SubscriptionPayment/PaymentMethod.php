<?php

namespace App\Enums\SubscriptionPayment;

enum PaymentMethod: string
{
    case Card         = 'card';
    case QRIS         = 'qris';
    case Gopay        = 'gopay';
    case ShopeePay    = 'shopeepay';
    case BankTransfer = 'bank_transfer';
    case EChannel     = 'echannel';
    case CStore       = 'cstore';
    case Akulaku      = 'akulaku';
}
