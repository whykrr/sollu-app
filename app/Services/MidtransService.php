<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createTransaction($params)
    {
        if (isset($params['transaction_details']['gross_amount'])) {
            $params['transaction_details']['gross_amount'] = (int) round($params['transaction_details']['gross_amount']);
        }

        if (isset($params['item_details']) && is_array($params['item_details'])) {
            foreach ($params['item_details'] as &$item) {
                if (isset($item['price'])) {
                    $item['price'] = (int) round($item['price']);
                }
            }
            unset($item);
        }

        return Snap::createTransaction($params);
    }
}
