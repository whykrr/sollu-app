<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PaymentTermEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use PHPUnit\Framework\TestCase;

class TransactionEnumsTest extends TestCase
{
    public function test_transaction_type_enum_cases_and_labels(): void
    {
        $this->assertSame('invoice', TransactionTypeEnum::Invoice->value);
        $this->assertSame('walk_in', TransactionTypeEnum::WalkIn->value);
        $this->assertSame('dine_in', TransactionTypeEnum::DineIn->value);
        $this->assertSame('take_away', TransactionTypeEnum::TakeAway->value);

        $this->assertSame('Penjualan', TransactionTypeEnum::Invoice->label());
        $this->assertSame('Walk-In', TransactionTypeEnum::WalkIn->label());
        $this->assertSame('Dine In', TransactionTypeEnum::DineIn->label());
        $this->assertSame('Take Away', TransactionTypeEnum::TakeAway->label());

        $this->assertSame('info', TransactionTypeEnum::Invoice->color());
        $this->assertSame('primary', TransactionTypeEnum::WalkIn->color());
        $this->assertSame('amber', TransactionTypeEnum::DineIn->color());
        $this->assertSame('purple', TransactionTypeEnum::TakeAway->color());

        $this->assertFalse(TransactionTypeEnum::Invoice->isPosType());
        $this->assertTrue(TransactionTypeEnum::WalkIn->isPosType());
        $this->assertTrue(TransactionTypeEnum::DineIn->isPosType());
        $this->assertTrue(TransactionTypeEnum::TakeAway->isPosType());
    }

    public function test_sales_channel_enum_cases_and_labels(): void
    {
        $this->assertSame('pos', SalesChannelEnum::POS->value);
        $this->assertSame('wholesale', SalesChannelEnum::Wholesale->value);

        $this->assertSame('Kasir POS', SalesChannelEnum::POS->label());
        $this->assertSame('Wholesale (Penjualan)', SalesChannelEnum::Wholesale->label());

        $this->assertSame('primary', SalesChannelEnum::POS->color());
        $this->assertSame('purple', SalesChannelEnum::Wholesale->color());

        $this->assertSame('POS', SalesChannelEnum::POS->initial());
        $this->assertSame('WS', SalesChannelEnum::Wholesale->initial());
    }

    public function test_payment_term_enum_cases_and_labels(): void
    {
        $this->assertSame('cash', PaymentTermEnum::Cash->value);
        $this->assertSame('credit', PaymentTermEnum::Credit->value);

        $this->assertSame('Tunai (Langsung Lunas / COD)', PaymentTermEnum::Cash->label());
        $this->assertSame('Termin / Kredit (Tempo)', PaymentTermEnum::Credit->label());
    }

    public function test_transaction_status_enum_cases_labels_and_colors(): void
    {
        $cases = [
            ['status' => TransactionStatus::Draft, 'label' => 'Draf', 'color' => 'neutral'],
            ['status' => TransactionStatus::Hold, 'label' => 'Ditahan', 'color' => 'warning'],
            ['status' => TransactionStatus::Completed, 'label' => 'Selesai', 'color' => 'success'],
            ['status' => TransactionStatus::Void, 'label' => 'Dibatalkan (Void)', 'color' => 'danger'],
            ['status' => TransactionStatus::Cancel, 'label' => 'Batal', 'color' => 'danger'],
            ['status' => TransactionStatus::Paid, 'label' => 'Lunas', 'color' => 'success'],
            ['status' => TransactionStatus::Unpaid, 'label' => 'Belum Dibayar', 'color' => 'neutral'],
            ['status' => TransactionStatus::Partial, 'label' => 'Dibayar Sebagian', 'color' => 'info'],
        ];

        foreach ($cases as $case) {
            $this->assertSame($case['label'], $case['status']->label());
            $this->assertSame($case['color'], $case['status']->color());
        }
    }

    public function test_transaction_payment_status_enum_cases_labels_and_colors(): void
    {
        $cases = [
            ['status' => TransactionPaymentStatus::Draft, 'label' => 'Draf', 'color' => 'neutral'],
            ['status' => TransactionPaymentStatus::Unpaid, 'label' => 'Belum Dibayar', 'color' => 'danger'],
            ['status' => TransactionPaymentStatus::Partial, 'label' => 'Dibayar Sebagian', 'color' => 'warning'],
            ['status' => TransactionPaymentStatus::Paid, 'label' => 'Lunas', 'color' => 'success'],
        ];

        foreach ($cases as $case) {
            $this->assertSame($case['label'], $case['status']->label());
            $this->assertSame($case['color'], $case['status']->color());
        }
    }
}
