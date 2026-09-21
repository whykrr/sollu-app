<?php

namespace Tests\Unit\Enums;

use App\Enums\InventoryMovementType;
use App\Support\Enums\FrontendEnumProvider;
use Tests\TestCase;

class InventoryMovementTypeTest extends TestCase
{
    /**
     * Pastikan semua case memiliki label, color, group, dan groupLabel.
     */
    public function test_all_cases_have_metadata(): void
    {
        foreach (InventoryMovementType::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Label untuk case '{$case->value}' tidak boleh kosong.");
            $this->assertNotEmpty($case->color(), "Color untuk case '{$case->value}' tidak boleh kosong.");
            $this->assertNotEmpty($case->group(), "Group untuk case '{$case->value}' tidak boleh kosong.");
            $this->assertNotEmpty($case->groupLabel(), "Group label untuk case '{$case->value}' tidak boleh kosong.");
        }
    }

    /**
     * Pastikan InitialStock memiliki label 'Stok Awal' dan color 'badge-main'.
     */
    public function test_initial_stock_case_metadata(): void
    {
        $initialStock = InventoryMovementType::InitialStock;

        $this->assertEquals('initial_stock', $initialStock->value);
        $this->assertEquals('Stok Awal', $initialStock->label());
        $this->assertEquals('badge-main', $initialStock->color());
        $this->assertEquals('initial', $initialStock->group());
        $this->assertEquals('Stok Awal', $initialStock->groupLabel());
        $this->assertTrue($initialStock->isIncrease());
        $this->assertFalse($initialStock->isDecrease());
    }

    /**
     * Pastikan method grouped menghasilkan array grup valid.
     */
    public function test_grouped_returns_valid_structure(): void
    {
        $grouped = InventoryMovementType::grouped();

        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('Stok Awal', $grouped);
        $this->assertArrayHasKey('Pembelian & Pengadaan', $grouped);
        $this->assertArrayHasKey('Penjualan & Resep', $grouped);
        $this->assertArrayHasKey('Penyesuaian & Opname', $grouped);
        $this->assertArrayHasKey('Transfer Stok', $grouped);

        $totalItems = array_reduce($grouped, fn ($carry, $items) => $carry + count($items), 0);
        $this->assertEquals(count(InventoryMovementType::cases()), $totalItems);
    }

    /**
     * Pastikan InventoryMovementType terdaftar dan ter-transformasi rapi di FrontendEnumProvider.
     */
    public function test_registered_in_frontend_enum_provider(): void
    {
        $data = FrontendEnumProvider::transform(InventoryMovementType::class);

        $this->assertEquals('initial_stock', $data['InitialStock']);
        $this->assertArrayHasKey('_meta', $data);
        $this->assertArrayHasKey('_options', $data);
        $this->assertArrayHasKey('_grouped', $data);

        $initialMeta = $data['_meta']['initial_stock'];
        $this->assertEquals('Stok Awal', $initialMeta['label']);
        $this->assertEquals('badge-main', $initialMeta['color']);
    }
}
