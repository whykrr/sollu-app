<?php

namespace Tests\Unit\Enums;

use App\Enums\StockTransferStatus;
use Tests\TestCase;

class StockTransferStatusTest extends TestCase
{
    public function test_all_cases_have_valid_values(): void
    {
        $this->assertEquals('pending', StockTransferStatus::Pending->value);
        $this->assertEquals('approved', StockTransferStatus::Approved->value);
        $this->assertEquals('in_transit', StockTransferStatus::InTransit->value);
        $this->assertEquals('completed', StockTransferStatus::Completed->value);
        $this->assertEquals('rejected', StockTransferStatus::Rejected->value);
        $this->assertEquals('void', StockTransferStatus::Void->value);
    }

    public function test_all_cases_have_labels(): void
    {
        $this->assertEquals('Menunggu', StockTransferStatus::Pending->label());
        $this->assertEquals('Disetujui', StockTransferStatus::Approved->label());
        $this->assertEquals('Dalam Perjalanan', StockTransferStatus::InTransit->label());
        $this->assertEquals('Selesai', StockTransferStatus::Completed->label());
        $this->assertEquals('Ditolak', StockTransferStatus::Rejected->label());
        $this->assertEquals('Dibatalkan', StockTransferStatus::Void->label());
    }

    public function test_all_cases_have_colors(): void
    {
        $this->assertEquals('badge-warning', StockTransferStatus::Pending->color());
        $this->assertEquals('badge-info', StockTransferStatus::Approved->color());
        $this->assertEquals('badge-purple', StockTransferStatus::InTransit->color());
        $this->assertEquals('badge-success', StockTransferStatus::Completed->color());
        $this->assertEquals('badge-danger', StockTransferStatus::Rejected->color());
        $this->assertEquals('badge-danger', StockTransferStatus::Void->color());
    }
}
