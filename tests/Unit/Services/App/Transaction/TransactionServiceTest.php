<?php

namespace Tests\Unit\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\User;
use App\Services\App\Transaction\PriceCalculationService;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $priceCalculationServiceMock;
    protected $auditLoggerMock;
    protected $service;
    protected $business;
    protected $outlet;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'business@test.com',
            'phone' => '08000000',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Test Outlet',
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Test Item',
            'sku' => 'SKU-001',
            'item_type' => 'variant_sku',
            'current_stock' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        // Create stock balance
        \App\Models\Inventory\InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 10,
        ]);

        $this->priceCalculationServiceMock = Mockery::mock(PriceCalculationService::class);
        $this->auditLoggerMock = Mockery::mock(ActivityLoggerInterface::class);

        $this->service = new TransactionService(
            $this->priceCalculationServiceMock,
            $this->auditLoggerMock
        );
    }

    public function test_check_stock_availability_allows_valid_stock(): void
    {
        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 5,
                'product_name' => 'Test Item',
            ],
        ];

        // Should not throw exception
        $this->service->checkStockAvailability($items, $this->outlet->id);
        
        $this->assertTrue(true);
    }

    public function test_check_stock_availability_throws_error_if_stock_insufficient(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'allow_negative_stock',
            'value' => [false],
            'type' => 'boolean',
        ]);

        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 15,
                'product_name' => 'Test Item',
            ],
        ];

        $this->expectException(ValidationException::class);
        
        $this->service->checkStockAvailability($items, $this->outlet->id);
    }

    public function test_check_stock_availability_allows_negative_stock_if_setting_enabled(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'allow_negative_stock',
            'value' => [true],
            'type' => 'boolean',
        ]);

        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 15, // Requested more than available (10)
                'product_name' => 'Test Item',
            ],
        ];

        // Should not throw exception
        $this->service->checkStockAvailability($items, $this->outlet->id);
        
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
