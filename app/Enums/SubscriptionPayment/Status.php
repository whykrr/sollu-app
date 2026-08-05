<?php

namespace App\Enums\SubscriptionPayment;

enum Status: string
{
    case Request       = 'request';
    case Pending       = 'pending';
    case Capture       = 'capture';
    case Settlement    = 'settlement';
    case Failed        = 'failed';
    case Expire        = 'expire';
    case Deny          = 'deny';
    case Cancel        = 'cancel';
    case Failure       = 'failure';
    case PartialRefund = 'partial_refund';
    case Authorize     = 'authorize';
}
