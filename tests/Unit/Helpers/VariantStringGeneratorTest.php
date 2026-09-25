<?php

namespace Tests\Unit\Helpers;

use App\Helpers\VariantStringGenerator;
use PHPUnit\Framework\TestCase;

class VariantStringGeneratorTest extends TestCase
{
    public function test_it_generates_ordered_variant_string_from_multiple_parts(): void
    {
        $result = VariantStringGenerator::generate(['Baju', 'Red', 'XL']);

        $this->assertEquals('BRXadejlu', $result);
    }

    public function test_it_handles_single_part(): void
    {
        $result = VariantStringGenerator::generate(['Kaos']);

        $this->assertEquals('Kaos', $result);
    }

    public function test_it_returns_empty_string_when_parts_array_is_empty(): void
    {
        $result = VariantStringGenerator::generate([]);

        $this->assertEquals('', $result);
    }

    public function test_it_ignores_empty_and_whitespace_only_parts(): void
    {
        $result = VariantStringGenerator::generate(['   ', 'Baju', '', '  ', 'Merah']);

        // Prefix: 'B' + 'M' = 'BM'
        // Rest: 'aju' + 'erah' => sorted 'aaehjru'
        $this->assertEquals('BMaaehjru', $result);
    }

    public function test_it_strips_special_characters_and_symbols(): void
    {
        $result = VariantStringGenerator::generate(['T-Shirt', 'Size: L/XL']);

        // Prefix: 'T' + 'S' => 'TS'
        // Rest stripped of symbols: 'shirt' + 'izelxl' => sorted chars
        $this->assertStringStartsWith('TS', $result);
        $this->assertDoesNotMatchRegularExpression('/[^a-zA-Z0-9]/', $result);
    }

    public function test_it_handles_multibyte_and_unicode_characters(): void
    {
        $result = VariantStringGenerator::generate(['Café', 'Crème']);

        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('CC', $result);
    }

    public function test_it_maintains_deterministic_order_with_lowercase_inputs(): void
    {
        $result = VariantStringGenerator::generate(['merah', 'biru']);

        // Prefix: 'M' + 'B' = 'MB'
        // Rest: 'erah' + 'iru' => sorted 'aehirru'
        $this->assertEquals('MBaehirru', $result);
    }
}
