<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\ProductTypeEnum;
use App\Events\Pos\PosCatalogNudgeEvent;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductPrice;
use App\Models\Outlet;
use App\Models\Uom;
use App\Services\Pos\PosNudgeQueueService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PosCatalogNudgeTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'description' => 'Retail Business Type']
        );

        $this->business = Business::create([
            'name' => 'Test Business Nudge',
            'owner_name' => 'Nudge Owner',
            'email' => 'nudge_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Nudge A',
            'address' => 'Jl. Nudge 1',
            'phone' => '08123456789',
        ]);
    }

    public function test_product_update_dispatches_pos_catalog_nudge_event(): void
    {
        Event::fake([PosCatalogNudgeEvent::class]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'code' => 'NUDGE-001',
            'name' => 'Test Nudge Product',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        DB::table('outlet_product')->insert([
            'outlet_id' => $this->outlet->id,
            'product_id' => $product->id,
            'is_enabled' => true,
            'is_available' => true,
        ]);

        // Trigger update to verify observer
        $product->update(['name' => 'Test Nudge Product Updated']);

        Event::assertDispatched(PosCatalogNudgeEvent::class, function (PosCatalogNudgeEvent $event) {
            $entities = (array) $event->entityType;

            return (string) $event->outletId === (string) $this->outlet->id
                && in_array('product', $entities, true);
        });
    }

    public function test_product_item_update_dispatches_pos_catalog_nudge_event(): void
    {
        Event::fake([PosCatalogNudgeEvent::class]);

        $uom = Uom::where('code', 'PCS')->first() ?? Uom::firstOrCreate(['code' => 'PCS_NUDGE'], ['name' => 'Pieces']);

        $product = Product::create([
            'business_id' => $this->business->id,
            'code' => 'NUDGE-002',
            'name' => 'Test Item Nudge Product',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        DB::table('outlet_product')->insert([
            'outlet_id' => $this->outlet->id,
            'product_id' => $product->id,
            'is_enabled' => true,
            'is_available' => true,
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'uom_id' => $uom->id,
            'name' => 'Test Variant Item',
            'sku' => 'SKU-NUDGE-01',
            'is_active' => true,
            'sellable' => true,
        ]);

        $item->update(['name' => 'Test Variant Item Updated']);

        Event::assertDispatched(PosCatalogNudgeEvent::class, function (PosCatalogNudgeEvent $event) {
            $entities = (array) $event->entityType;

            return (string) $event->outletId === (string) $this->outlet->id
                && in_array('product_item', $entities, true);
        });
    }

    public function test_inventory_balance_update_dispatches_pos_catalog_nudge_event(): void
    {
        Event::fake([PosCatalogNudgeEvent::class]);

        $uom = Uom::where('code', 'PCS')->first() ?? Uom::firstOrCreate(['code' => 'PCS_NUDGE'], ['name' => 'Pieces']);

        $inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'uom_id' => $uom->id,
            'name' => 'Raw Item For Stock',
            'sku' => 'RAW-001',
        ]);

        $balance = InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
            'minimum_stock' => 2,
            'average_cost' => 5000,
            'last_cost' => 5000,
            'total_value' => 50000,
        ]);

        $balance->update(['current_stock' => 8]);

        Event::assertDispatched(PosCatalogNudgeEvent::class, function (PosCatalogNudgeEvent $event) {
            $entities = (array) $event->entityType;

            return (string) $event->outletId === (string) $this->outlet->id
                && in_array('inventory_balance', $entities, true);
        });
    }

    public function test_product_price_update_dispatches_pos_catalog_nudge_event(): void
    {
        Event::fake([PosCatalogNudgeEvent::class]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'code' => 'NUDGE-003',
            'name' => 'Test Price Nudge Product',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        DB::table('outlet_product')->insert([
            'outlet_id' => $this->outlet->id,
            'product_id' => $product->id,
            'is_enabled' => true,
            'is_available' => true,
        ]);

        $price = ProductPrice::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'amount' => 15000,
        ]);

        $price->update(['amount' => 18000]);

        Event::assertDispatched(PosCatalogNudgeEvent::class, function (PosCatalogNudgeEvent $event) {
            $entities = (array) $event->entityType;

            return (string) $event->outletId === (string) $this->outlet->id
                && in_array('product_price', $entities, true);
        });
    }

    public function test_multiple_model_updates_are_aggregated_into_single_nudge_event(): void
    {
        Event::fake([PosCatalogNudgeEvent::class]);
        PosNudgeQueueService::$deferInTests = true;

        try {
            $uom = Uom::where('code', 'PCS')->first() ?? Uom::firstOrCreate(['code' => 'PCS_NUDGE'], ['name' => 'Pieces']);

            $product = Product::create([
                'business_id' => $this->business->id,
                'code' => 'NUDGE-004',
                'name' => 'Test Aggregated Product',
                'product_type' => ProductTypeEnum::BASIC,
                'is_show' => true,
                'sellable' => true,
            ]);

            DB::table('outlet_product')->insert([
                'outlet_id' => $this->outlet->id,
                'product_id' => $product->id,
                'is_enabled' => true,
                'is_available' => true,
            ]);

            $item = ProductItem::create([
                'business_id' => $this->business->id,
                'product_id' => $product->id,
                'item_type' => 'variant_sku',
                'uom_id' => $uom->id,
                'name' => 'Test Item Aggregated',
                'is_active' => true,
                'sellable' => true,
            ]);

            $price = ProductPrice::create([
                'product_id' => $product->id,
                'product_item_id' => $item->id,
                'outlet_id' => $this->outlet->id,
                'amount' => 20000,
            ]);

            // Now perform multiple updates in one go (simulating request saving product, item, price)
            $product->update(['name' => 'Test Aggregated Product Updated']);
            $item->update(['name' => 'Test Item Aggregated Updated']);
            $price->update(['amount' => 25000]);

            $queue = app(PosNudgeQueueService::class);
            $this->assertTrue($queue->hasPendingSignals());

            // Flush the queue
            $queue->flush();

            // Assert exactly 1 event dispatched for this outlet
            Event::assertDispatched(PosCatalogNudgeEvent::class, 1);
            Event::assertDispatched(PosCatalogNudgeEvent::class, function (PosCatalogNudgeEvent $event) {
                $entities = (array) $event->entityType;

                return (string) $event->outletId === (string) $this->outlet->id
                    && in_array('product', $entities, true)
                    && in_array('product_item', $entities, true)
                    && in_array('product_price', $entities, true);
            });
        } finally {
            PosNudgeQueueService::$deferInTests = false;
        }
    }

    public function test_pos_catalog_nudge_event_structure_and_payload(): void
    {
        $event = new PosCatalogNudgeEvent($this->outlet->id, ['product', 'product_price']);

        $this->assertEquals('pos.catalog.nudge', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals("private-outlet.{$this->outlet->id}.pos", $channels[0]->name);

        $payload = $event->broadcastWith();
        $this->assertEquals('pos.catalog.nudge', $payload['event']);
        $this->assertEquals($this->outlet->id, $payload['outlet_id']);
        $this->assertEquals('product,product_price', $payload['entity_type']);
        $this->assertEquals(['product', 'product_price'], $payload['entities']);
        $this->assertArrayHasKey('timestamp', $payload);
    }
}
