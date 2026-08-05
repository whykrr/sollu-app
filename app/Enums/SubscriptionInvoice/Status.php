<?php

namespace App\Enums\SubscriptionInvoice;

enum Status: string
{
    case Unpaid   = 'unpaid';
    case Payment  = 'payment';
    case Paid     = 'paid';
    case Expired  = 'expired';
    case Canceled = 'canceled';
}
