<?php

namespace Tests\Unit\Services\Cockpit;

use App\Models\Business;
use App\Models\BusinessType;
use App\Services\Cockpit\BusinessTypeService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTypeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BusinessTypeService;
    }

    public function test_create_business_type(): void
    {
        $payload = [
            'code' => 'minimarket',
            'name' => 'Minimarket',
            'sort_order' => 1,
            'is_visible' => true,
            'features' => ['pos_cashier', 'shift_management'],
        ];

        $businessType = $this->service->create($payload);

        $this->assertInstanceOf(BusinessType::class, $businessType);
        $this->assertSame('minimarket', $businessType->code);
        $this->assertSame('Minimarket', $businessType->name);
        $this->assertSame(1, $businessType->sort_order);
        $this->assertTrue($businessType->is_visible);
        $this->assertSame(['pos_cashier', 'shift_management'], $businessType->features);

        $this->assertDatabaseHas('business_types', [
            'id' => $businessType->id,
            'code' => 'minimarket',
            'name' => 'Minimarket',
        ]);
    }

    public function test_update_business_type(): void
    {
        $businessType = BusinessType::create([
            'code' => 'retail',
            'name' => 'Toko Retail',
            'sort_order' => 5,
            'is_visible' => true,
        ]);

        $updated = $this->service->update($businessType, [
            'code' => 'retail_super',
            'name' => 'Toko Retail Super',
            'sort_order' => 2,
            'is_visible' => false,
        ]);

        $this->assertSame('retail_super', $updated->code);
        $this->assertSame('Toko Retail Super', $updated->name);
        $this->assertSame(2, $updated->sort_order);
        $this->assertFalse($updated->is_visible);

        $this->assertDatabaseHas('business_types', [
            'id' => $businessType->id,
            'code' => 'retail_super',
            'name' => 'Toko Retail Super',
        ]);
    }

    public function test_toggle_visibility(): void
    {
        $businessType = BusinessType::create([
            'code' => 'cafe',
            'name' => 'Cafe',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $this->assertTrue($businessType->is_visible);

        $toggled = $this->service->toggleVisibility($businessType);
        $this->assertFalse($toggled->is_visible);

        $toggledAgain = $this->service->toggleVisibility($businessType);
        $this->assertTrue($toggledAgain->is_visible);
    }

    public function test_update_features(): void
    {
        $businessType = BusinessType::create([
            'code' => 'coffee_shop',
            'name' => 'Coffee Shop',
            'sort_order' => 1,
            'is_visible' => true,
            'features' => ['pos_cashier'],
        ]);

        $newFeatures = ['pos_cashier', 'recipe_management', 'raw_materials', 'pos_cashier'];

        $updated = $this->service->updateFeatures($businessType, $newFeatures);

        $this->assertCount(3, $updated->features);
        $this->assertEqualsCanonicalizing(['pos_cashier', 'recipe_management', 'raw_materials'], $updated->features);

        $this->assertDatabaseHas('business_types', [
            'id' => $businessType->id,
        ]);
    }

    public function test_delete_unused_business_type(): void
    {
        $businessType = BusinessType::create([
            'code' => 'temporary_type',
            'name' => 'Temporary Type',
            'sort_order' => 99,
            'is_visible' => false,
        ]);

        $result = $this->service->delete($businessType);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('business_types', [
            'id' => $businessType->id,
        ]);
    }

    public function test_cannot_delete_business_type_with_associated_businesses(): void
    {
        $businessType = BusinessType::create([
            'code' => 'fnb',
            'name' => 'F&B Food',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        // Create a business referencing this business_type
        Business::create([
            'name' => 'Test Restaurant',
            'owner_name' => 'Owner Test',
            'email' => 'owner@restaurant.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $businessType->id,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Jenis bisnis {$businessType->name} tidak dapat dihapus karena masih digunakan oleh merchant aktif.");

        $this->service->delete($businessType);
    }
}
