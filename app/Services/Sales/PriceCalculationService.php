<?php

namespace App\Services\Sales;

class PriceCalculationService
{
    public function calculate(array $items, float $discount = 0, float $taxRate = 0, float $serviceChargeRate = 0): array
    {
        // Scaffold
        return [
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 0,
        ];
    }
}
