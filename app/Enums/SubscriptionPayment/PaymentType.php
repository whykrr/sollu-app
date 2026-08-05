<?php

namespace App\Enums\SubscriptionPayment;

enum PaymentType: string
{
    case MIDTRANS = 'midtrans';
    case XENDIT   = 'xendit';
}
