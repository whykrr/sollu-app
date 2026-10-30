<?php

namespace Tests\Unit\Enums;

use App\Enums\StockTransferMode;
use Tests\TestCase;

class StockTransferModeTest extends TestCase
{
    public function test_all_cases_have_valid_values(): void
    {
        $this->assertEquals('direct', StockTransferMode::DIRECT->value);
        $this->assertEquals('two_step', StockTransferMode::TWO_STEP->value);
    }

    public function test_all_cases_have_labels(): void
    {
        $this->assertEquals('Transfer Langsung (1-Langkah)', StockTransferMode::DIRECT->label());
        $this->assertEquals('Kirim & Terima (2-Langkah)', StockTransferMode::TWO_STEP->label());
    }

    public function test_all_cases_have_short_labels(): void
    {
        $this->assertEquals('1-Langkah', StockTransferMode::DIRECT->shortLabel());
        $this->assertEquals('2-Langkah', StockTransferMode::TWO_STEP->shortLabel());
    }

    public function test_all_cases_have_colors(): void
    {
        $this->assertEquals('badge-info', StockTransferMode::DIRECT->color());
        $this->assertEquals('badge-purple', StockTransferMode::TWO_STEP->color());
    }
}
