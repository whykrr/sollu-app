<?php

namespace Tests\Unit\Enums;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Support\Enums\FrontendEnumProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PromotionEnumsTest extends TestCase
{
    public function test_promotion_status_cases_and_labels(): void
    {
        $this->assertEquals('draft', PromotionStatus::Draft->value);
        $this->assertEquals('active', PromotionStatus::Active->value);
        $this->assertEquals('inactive', PromotionStatus::Inactive->value);
        $this->assertEquals('expired', PromotionStatus::Expired->value);

        $this->assertEquals('Draf', PromotionStatus::Draft->label());
        $this->assertEquals('Aktif', PromotionStatus::Active->label());
        $this->assertEquals('Nonaktif', PromotionStatus::Inactive->label());
        $this->assertEquals('Kedaluwarsa', PromotionStatus::Expired->label());

        $this->assertEquals('neutral', PromotionStatus::Draft->color());
        $this->assertEquals('success', PromotionStatus::Active->color());
        $this->assertEquals('warning', PromotionStatus::Inactive->color());
        $this->assertEquals('danger', PromotionStatus::Expired->color());
    }

    public function test_promotion_application_mode_cases_and_labels(): void
    {
        $this->assertEquals('automatic', PromotionApplicationMode::Automatic->value);
        $this->assertEquals('manual', PromotionApplicationMode::Manual->value);

        $this->assertEquals('Otomatis', PromotionApplicationMode::Automatic->label());
        $this->assertEquals('Kode Promo (Manual)', PromotionApplicationMode::Manual->label());
    }

    public function test_promotion_target_scope_cases_and_labels(): void
    {
        $this->assertEquals('transaction', PromotionTargetScope::Transaction->value);
        $this->assertEquals('category', PromotionTargetScope::Category->value);
        $this->assertEquals('product', PromotionTargetScope::Product->value);
        $this->assertEquals('variant', PromotionTargetScope::Variant->value);

        $this->assertEquals('Seluruh Transaksi', PromotionTargetScope::Transaction->label());
        $this->assertEquals('Kategori Produk', PromotionTargetScope::Category->label());
        $this->assertEquals('Produk Spesifik', PromotionTargetScope::Product->label());
        $this->assertEquals('Varian Produk (SKU)', PromotionTargetScope::Variant->label());
    }

    public function test_promotion_discount_type_cases_and_labels(): void
    {
        $this->assertEquals('percentage', PromotionDiscountType::Percentage->value);
        $this->assertEquals('fixed', PromotionDiscountType::Fixed->value);

        $this->assertEquals('Persentase (%)', PromotionDiscountType::Percentage->label());
        $this->assertEquals('Nominal Tetap (Rp)', PromotionDiscountType::Fixed->label());
    }

    public function test_promotion_enums_registered_in_frontend_enum_provider(): void
    {
        $reflection = new ReflectionClass(FrontendEnumProvider::class);
        $property = $reflection->getProperty('frontendEnums');
        $registered = $property->getValue();

        $this->assertContains(PromotionStatus::class, $registered);
        $this->assertContains(PromotionApplicationMode::class, $registered);
        $this->assertContains(PromotionTargetScope::class, $registered);
        $this->assertContains(PromotionDiscountType::class, $registered);

        $this->assertNotContains('App\Enums\PromoStatus', $registered);
        $this->assertNotContains('App\Enums\PromoTarget', $registered);
        $this->assertNotContains('App\Enums\PromoType', $registered);
    }

    public function test_promotion_enums_transform_structure(): void
    {
        $statusTransformed = FrontendEnumProvider::transform(PromotionStatus::class);
        $this->assertEquals('draft', $statusTransformed['Draft']);
        $this->assertEquals('Draf', $statusTransformed['_meta']['draft']['label']);
        $this->assertEquals('neutral', $statusTransformed['_meta']['draft']['color']);
        $this->assertIsArray($statusTransformed['_options']);

        $modeTransformed = FrontendEnumProvider::transform(PromotionApplicationMode::class);
        $this->assertEquals('automatic', $modeTransformed['Automatic']);
        $this->assertEquals('Otomatis', $modeTransformed['_meta']['automatic']['label']);

        $scopeTransformed = FrontendEnumProvider::transform(PromotionTargetScope::class);
        $this->assertEquals('transaction', $scopeTransformed['Transaction']);
        $this->assertEquals('Seluruh Transaksi', $scopeTransformed['_meta']['transaction']['label']);

        $typeTransformed = FrontendEnumProvider::transform(PromotionDiscountType::class);
        $this->assertEquals('percentage', $typeTransformed['Percentage']);
        $this->assertEquals('Persentase (%)', $typeTransformed['_meta']['percentage']['label']);
    }
}
