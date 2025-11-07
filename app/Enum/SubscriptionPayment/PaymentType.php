<?php

namespace App\Enum\SubscriptionPayment;

enum PaymentType: string
{
    case MIDTRANS = 'midtrans';
    case XENDIT   = 'xendit';
}
