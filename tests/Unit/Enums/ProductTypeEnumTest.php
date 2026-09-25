<?php

namespace Tests\Unit\Enums;

use App\Enums\ProductTypeEnum;
use Tests\TestCase;

class ProductTypeEnumTest extends TestCase
{
    public function test_all_cases_have_valid_values(): void
    {
        $this->assertEquals('basic', ProductTypeEnum::BASIC->value);
        $this->assertEquals('service', ProductTypeEnum::SERVICE->value);
        $this->assertEquals('bundle', ProductTypeEnum::BUNDLE->value);
    }

    public function test_all_cases_have_labels(): void
    {
        $this->assertEquals('Produk Barang', ProductTypeEnum::BASIC->label());
        $this->assertEquals('Produk Layanan', ProductTypeEnum::SERVICE->label());
        $this->assertEquals('Paket Bundle', ProductTypeEnum::BUNDLE->label());
    }

    public function test_all_cases_have_badge_colors(): void
    {
        $this->assertNotEmpty(ProductTypeEnum::BASIC->badgeColor());
        $this->assertNotEmpty(ProductTypeEnum::SERVICE->badgeColor());
        $this->assertNotEmpty(ProductTypeEnum::BUNDLE->badgeColor());

        $this->assertEquals(ProductTypeEnum::BASIC->badgeColor(), ProductTypeEnum::BASIC->color());
    }

    public function test_all_cases_have_icons(): void
    {
        $this->assertNotEmpty(ProductTypeEnum::BASIC->icon());
        $this->assertNotEmpty(ProductTypeEnum::SERVICE->icon());
        $this->assertNotEmpty(ProductTypeEnum::BUNDLE->icon());
    }

    public function test_values_helper_returns_all_enum_values(): void
    {
        $values = ProductTypeEnum::values();

        $this->assertCount(3, $values);
        $this->assertContains('basic', $values);
        $this->assertContains('service', $values);
        $this->assertContains('bundle', $values);
    }

    public function test_options_helper_returns_key_value_pairs(): void
    {
        $options = ProductTypeEnum::options();

        $this->assertArrayHasKey('basic', $options);
        $this->assertArrayHasKey('service', $options);
        $this->assertArrayHasKey('bundle', $options);
        $this->assertEquals('Produk Barang', $options['basic']);
        $this->assertEquals('Produk Layanan', $options['service']);
        $this->assertEquals('Paket Bundle', $options['bundle']);
    }
}
