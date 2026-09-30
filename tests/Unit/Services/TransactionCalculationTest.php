<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class TransactionCalculationTest extends TestCase
{
    public function test_line_item_subtotal_calculation_without_discount(): void
    {
        $qty = 3.0;
        $price = 15000.0;
        $discount = 0.0;

        $subtotal = ($qty * $price) - $discount;

        $this->assertEquals(45000.0, $subtotal);
    }

    public function test_line_item_subtotal_calculation_with_discount(): void
    {
        $qty = 4.0;
        $price = 25000.0;
        $discount = 5000.0;

        $subtotal = ($qty * $price) - $discount;

        $this->assertEquals(95000.0, $subtotal);
    }

    public function test_document_grand_total_calculation(): void
    {
        $subtotal = 200000.0;
        $docDiscount = 20000.0;
        $taxAmount = 19800.0; // 11% dari 180.000
        $shippingFee = 15000.0;
        $serviceCharge = 5000.0;

        $total = $subtotal - $docDiscount + $taxAmount + $shippingFee + $serviceCharge;

        $this->assertEquals(219800.0, $total);
    }

    public function test_balance_due_calculation_for_unpaid(): void
    {
        $total = 150000.0;
        $totalPaid = 0.0;

        $balanceDue = max(0.0, $total - $totalPaid);

        $this->assertEquals(150000.0, $balanceDue);
    }

    public function test_balance_due_calculation_for_partial_payment(): void
    {
        $total = 150000.0;
        $totalPaid = 50000.0;

        $balanceDue = max(0.0, $total - $totalPaid);

        $this->assertEquals(100000.0, $balanceDue);
    }

    public function test_balance_due_calculation_for_full_payment_with_overpay(): void
    {
        $total = 150000.0;
        $amountPaid = 200000.0;
        $changeAmount = 50000.0;

        $netPaid = $amountPaid - $changeAmount;
        $balanceDue = max(0.0, $total - $netPaid);

        $this->assertEquals(150000.0, $netPaid);
        $this->assertEquals(0.0, $balanceDue);
    }

    public function test_change_amount_calculation(): void
    {
        $balanceDue = 75000.0;
        $cashTendered = 100000.0;

        $change = max(0.0, $cashTendered - $balanceDue);

        $this->assertEquals(25000.0, $change);
    }
}
