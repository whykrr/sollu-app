<?php

namespace Tests\Unit\Settings;

use App\Settings\ProductSettings;
use PHPUnit\Framework\TestCase;

class ProductSettingsTest extends TestCase
{
    public function test_it_initializes_with_default_values(): void
    {
        $settings = new ProductSettings;

        $this->assertFalse($settings->variant);
    }

    public function test_it_initializes_with_custom_array_data(): void
    {
        $settings = new ProductSettings(['variant' => true]);

        $this->assertTrue($settings->variant);
    }

    public function test_it_converts_to_array(): void
    {
        $settings = new ProductSettings(['variant' => true]);

        $this->assertEquals([
            'variant' => true,
        ], $settings->toArray());
    }
}
