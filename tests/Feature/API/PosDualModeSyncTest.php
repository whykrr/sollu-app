<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductPrice;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Uom;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosDualModeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected OutletDevice $device;

    protected PosDeviceAuthCacheService $cacheService;

    protected ProductCategory $category;

    protected Product $product;

    protected ProductItem $productItem;

    protected InventoryItem $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->cacheService = app(PosDeviceAuthCacheService::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Dual Mode Sync Merchant',
            'owner_name' => 'Owner',
            'email' => 'sync_merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Sync Test',
            'is_active' => true,
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'POS Dual Mode 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'dev-uuid-dual-001',
            'hardware_fingerprint' => 'hw-sig-dual-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        // Kategori & Produk
        $this->category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Coffee',
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'product_category_id' => $this->category->id,
            'product_type' => ProductTypeEnum::BASIC->value,
            'name' => 'Kopi Susu Gula Aren',
            'code' => 'PRD-001',
            'is_show' => true,
            'sellable' => true,
        ]);

        $uom = Uom::first() ?? Uom::create([
            'name' => 'Pcs',
            'code' => 'PCS',
        ]);

        $this->productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'uom_id' => $uom->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Susu Gula Aren Reguler',
            'sku' => 'SKU-001',
            'barcode' => 'BAR-001',
            'is_active' => true,
            'sellable' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->productItem->id,
            'uom_id' => $uom->id,
            'name' => 'Kopi Susu Item',
            'is_active' => true,
        ]);

        // Aktifkan produk pada outlet
        DB::table('outlet_product')->insert([
            'outlet_id' => $this->outlet->id,
            'product_id' => $this->product->id,
            'is_enabled' => true,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Harga
        ProductPrice::create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'product_item_id' => $this->productItem->id,
            'amount' => 25000,
        ]);

        // Saldo stok
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 50,
        ]);
    }

    public function test_initial_sync_returns_full_snapshot_with_synced_at(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-dual-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-dual-001',
        ])->getJson('http://api.sollu.test/v1/pos/sync/initial');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('synced_at', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('product_prices', $data);
        $this->assertArrayHasKey('inventory_balances', $data);
        $this->assertCount(1, $data['products']);
        $this->assertEquals('Kopi Susu Gula Aren', $data['products'][0]['name']);
    }

    public function test_delta_sync_returns_empty_when_no_updates(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $futureTimestamp = now()->addMinute()->toIso8601String();

        $futureParam = urlencode($futureTimestamp);
        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-dual-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-dual-001',
        ])->getJson("http://api.sollu.test/v1/pos/sync/delta?updated_since={$futureParam}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('synced_at', $data);
        $this->assertEmpty($data['updated_products']);
        $this->assertEmpty($data['updated_product_items']);
        $this->assertEmpty($data['updated_prices']);
        $this->assertEmpty($data['updated_inventory_balances']);
        $this->assertEmpty($data['deleted_product_ids']);
    }

    public function test_delta_sync_returns_updated_records_after_modification(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $checkpoint = now()->subSeconds(2)->toIso8601String();

        // Update product name
        $this->product->update(['name' => 'Kopi Susu Gula Aren Spesial']);

        // Update variant item barcode
        $this->productItem->update(['barcode' => 'BAR-001-NEW']);

        // Update balance
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)->first();
        $balance->update(['current_stock' => 45]);

        $checkParam = urlencode($checkpoint);
        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-dual-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-dual-001',
        ])->getJson("http://api.sollu.test/v1/pos/sync/delta?updated_since={$checkParam}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data['updated_products']);
        $this->assertEquals('Kopi Susu Gula Aren Spesial', $data['updated_products'][0]['name']);

        $this->assertCount(1, $data['updated_product_items']);
        $this->assertEquals('BAR-001-NEW', $data['updated_product_items'][0]['barcode']);

        $this->assertCount(1, $data['updated_inventory_balances']);
        $this->assertEquals(45, (float) $data['updated_inventory_balances'][0]['current_stock']);
    }

    public function test_delta_sync_tracks_deleted_products(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $checkpoint = now()->subSeconds(2)->toIso8601String();

        // Soft delete the product
        $this->product->delete();

        $checkParam = urlencode($checkpoint);
        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-dual-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-dual-001',
        ])->getJson("http://api.sollu.test/v1/pos/sync/delta?updated_since={$checkParam}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertContains($this->product->id, $data['deleted_product_ids']);
    }
}
