<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PaymentTermEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use PHPUnit\Framework\TestCase;

class TransactionEnumsTest extends TestCase
{
    public function test_sales_channel_enum_cases_and_labels(): void
    {
        $this->assertSame('wholesale', SalesChannelEnum::Wholesale->value);
        $this->assertSame('direct', SalesChannelEnum::Direct->value);

        $this->assertSame('Grosir (Wholesale)', SalesChannelEnum::Wholesale->label());
        $this->assertSame('Penjualan Langsung (Direct Sales)', SalesChannelEnum::Direct->label());
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
