<?php

namespace Tests\Feature\Master;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCreateWithInitialStockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Outlet $outlet2;

    protected Uom $uom;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Test Initial Stock Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'code' => 'OUT-01',
            'address' => 'Jl. Merdeka No. 1',
            'is_active' => true,
        ]);

        $this->outlet2 = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang',
            'code' => 'OUT-02',
            'address' => 'Jl. Sudirman No. 2',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Owner User',
            'email' => 'owner_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->uom = Uom::whereRaw('LOWER(code) = ?', ['pcs'])->first()
            ?? Uom::create(['code' => 'PCS', 'name' => 'Pieces', 'status' => 'active']);

        $this->subscribeBusinessToProPlan();
    }

    protected function subscribeBusinessToProPlan(): void
    {
        setPermissionsTeamId($this->business->id);
        $plan = SubscriptionPlan::firstOrCreate(
            ['code' => PlanEnum::PRO->value],
            ['name' => 'Pro Plan', 'is_active' => true]
        );

        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addDays(30),
        ]);

        $this->user->refresh();
    }

    public function test_create_single_product_with_initial_stock_records_costing_correctly(): void
    {
        $payload = [
            'name' => 'Kopi Susu Gula Aren',
            'code' => 'KOP-AREN',
            'barcode' => '899123456789',
            'product_type' => 'basic',
            'base_price' => 18000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'minimum_stock' => 5,
            'initial_stock' => 25,
            'purchase_price' => 10000,
            'outlet_id' => $this->outlet->id,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $product = Product::where('business_id', $this->business->id)
            ->where('name', 'Kopi Susu Gula Aren')
            ->first();

        $this->assertNotNull($product);

        $productItem = ProductItem::where('product_id', $product->id)->first();
        $this->assertNotNull($productItem);

        $invItem = InventoryItem::where('product_item_id', $productItem->id)->first();
        $this->assertNotNull($invItem);

        // Verifikasi InventoryBalance
        $balance = InventoryBalance::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(25, (float) $balance->current_stock);
        $this->assertEquals(10000, (float) $balance->average_cost);
        $this->assertEquals(10000, (float) $balance->last_cost);
        $this->assertEquals(250000, (float) $balance->total_value);

        // Verifikasi InventoryCostLayer
        $layer = InventoryCostLayer::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertNotNull($layer);
        $this->assertEquals($this->business->id, $layer->business_id);
        $this->assertEquals(25, (float) $layer->qty_purchased);
        $this->assertEquals(25, (float) $layer->qty_remaining);
        $this->assertEquals(10000, (float) $layer->purchase_price);

        // Verifikasi InventoryMovement
        $movement = InventoryMovement::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->where('movement_type', InventoryMovementType::InitialStock)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(25, (float) $movement->qty_change);
        $this->assertEquals(0, (float) $movement->stock_before);
        $this->assertEquals(25, (float) $movement->stock_after);
        $this->assertEquals(10000, (float) $movement->unit_cost);
        $this->assertEquals(250000, (float) $movement->total_cost);
    }

    public function test_create_variant_product_with_initial_stock_records_costing_for_each_variant(): void
    {
        $payload = [
            'name' => 'Kaos Polos',
            'code' => 'KAOS',
            'product_type' => 'basic',
            'base_price' => 50000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'has_variant' => true,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
            'variants' => [
                [
                    'name' => 'Ukuran',
                    'options' => [
                        ['name' => 'M'],
                        ['name' => 'L'],
                    ],
                ],
            ],
            'variant_combinations' => [
                [
                    'options' => ['Ukuran' => 'M'],
                    'sku' => 'KAOS-M',
                    'price' => 50000,
                    'initial_stock' => 10,
                    'purchase_price' => 25000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                    'is_show' => true,
                ],
                [
                    'options' => ['Ukuran' => 'L'],
                    'sku' => 'KAOS-L',
                    'price' => 55000,
                    'initial_stock' => 15,
                    'purchase_price' => 27000,
                    'track_inventory' => true,
                    'sellable' => true,
                    'is_active' => true,
                    'is_show' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $product = Product::where('business_id', $this->business->id)
            ->where('name', 'Kaos Polos')
            ->first();

        $this->assertNotNull($product);

        // Varian M
        $itemM = ProductItem::where('product_id', $product->id)->where('sku', 'KAOS-M')->first();
        $this->assertNotNull($itemM);
        $invItemM = InventoryItem::where('product_item_id', $itemM->id)->first();
        $balanceM = InventoryBalance::where('inventory_item_id', $invItemM->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($balanceM);
        $this->assertEquals(10, (float) $balanceM->current_stock);
        $this->assertEquals(25000, (float) $balanceM->average_cost);
        $this->assertEquals(250000, (float) $balanceM->total_value);

        // Varian L
        $itemL = ProductItem::where('product_id', $product->id)->where('sku', 'KAOS-L')->first();
        $this->assertNotNull($itemL);
        $invItemL = InventoryItem::where('product_item_id', $itemL->id)->first();
        $balanceL = InventoryBalance::where('inventory_item_id', $invItemL->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($balanceL);
        $this->assertEquals(15, (float) $balanceL->current_stock);
        $this->assertEquals(27000, (float) $balanceL->average_cost);
        $this->assertEquals(405000, (float) $balanceL->total_value);
    }

    public function test_create_product_with_multi_outlet_initial_stocks_allocation(): void
    {
        $payload = [
            'name' => 'Sirup Vanila',
            'code' => 'SIR-VAN',
            'product_type' => 'basic',
            'base_price' => 35000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
                ['outlet_id' => $this->outlet2->id, 'is_enabled' => true, 'is_available' => true],
            ],
            'initial_stocks' => [
                [
                    'outlet_id' => $this->outlet->id,
                    'qty' => 12,
                    'purchase_price' => 20000,
                ],
                [
                    'outlet_id' => $this->outlet2->id,
                    'qty' => 8,
                    'purchase_price' => 21000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $product = Product::where('business_id', $this->business->id)
            ->where('name', 'Sirup Vanila')
            ->first();

        $productItem = ProductItem::where('product_id', $product->id)->first();
        $invItem = InventoryItem::where('product_item_id', $productItem->id)->first();

        // Cek Outlet 1
        $balance1 = InventoryBalance::where('inventory_item_id', $invItem->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(12, (float) $balance1->current_stock);
        $this->assertEquals(20000, (float) $balance1->average_cost);

        // Cek Outlet 2
        $balance2 = InventoryBalance::where('inventory_item_id', $invItem->id)->where('outlet_id', $this->outlet2->id)->first();
        $this->assertEquals(8, (float) $balance2->current_stock);
        $this->assertEquals(21000, (float) $balance2->average_cost);
    }

    public function test_create_raw_material_with_initial_stock_records_costing(): void
    {
        $payload = [
            'name' => 'Biji Kopi Arabika Specialty',
            'sku' => 'BB-ARABIKA-01',
            'uom_id' => $this->uom->id,
            'track_inventory' => true,
            'minimum_stock' => 2,
            'initial_stock' => 10,
            'purchase_price' => 85000,
            'outlet_id' => $this->outlet->id,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/raw-materials", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invItem = InventoryItem::where('business_id', $this->business->id)
            ->where('name', 'Biji Kopi Arabika Specialty')
            ->first();

        $this->assertNotNull($invItem);

        $balance = InventoryBalance::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(10, (float) $balance->current_stock);
        $this->assertEquals(85000, (float) $balance->average_cost);
        $this->assertEquals(850000, (float) $balance->total_value);
    }

    public function test_update_product_persists_minimum_stock_and_syncs_to_balances(): void
    {
        $payload = [
            'name' => 'Kopi Tubruk',
            'base_price' => 12000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'minimum_stock' => 5,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
        ];

        $createResponse = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);
        $createResponse->assertSessionHasNoErrors();

        $product = Product::where('business_id', $this->business->id)->where('name', 'Kopi Tubruk')->first();
        $this->assertNotNull($product);

        $productItem = ProductItem::where('product_id', $product->id)->first();
        $invItem = InventoryItem::where('product_item_id', $productItem->id)->first();
        $this->assertEquals(5, (float) $invItem->minimum_stock);

        // Update product dengan minimum_stock baru
        $updatePayload = [
            'name' => 'Kopi Tubruk Premium',
            'base_price' => 15000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'minimum_stock' => 15,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
        ];

        $updateResponse = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);
        $updateResponse->assertSessionHasNoErrors();

        $invItem->refresh();
        $this->assertEquals(15, (float) $invItem->minimum_stock);

        $balance = InventoryBalance::where('inventory_item_id', $invItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();
        $this->assertEquals(15, (float) $balance->minimum_stock);
    }

    public function test_update_product_can_update_initial_stock_when_eligible(): void
    {
        $payload = [
            'name' => 'Teh Melati',
            'base_price' => 10000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'initial_stock' => 20,
            'purchase_price' => 5000,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
        ];

        $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $product = Product::where('business_id', $this->business->id)->where('name', 'Teh Melati')->first();
        $productItem = ProductItem::where('product_id', $product->id)->first();
        $invItem = InventoryItem::where('product_item_id', $productItem->id)->first();

        $balanceBefore = InventoryBalance::where('inventory_item_id', $invItem->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(20, (float) $balanceBefore->current_stock);
        $this->assertEquals(5000, (float) $balanceBefore->average_cost);

        // Update stok awal dan HPP
        $updatePayload = [
            'name' => 'Teh Melati',
            'base_price' => 10000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'initial_stock' => 35,
            'purchase_price' => 6000,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $this->outlet->id, 'is_enabled' => true, 'is_available' => true],
            ],
        ];

        $updateResponse = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);
        $updateResponse->assertSessionHasNoErrors();

        $balanceAfter = InventoryBalance::where('inventory_item_id', $invItem->id)->where('outlet_id', $this->outlet->id)->first();
        $this->assertEquals(35, (float) $balanceAfter->current_stock);
        $this->assertEquals(6000, (float) $balanceAfter->average_cost);
        $this->assertEquals(210000, (float) $balanceAfter->total_value);
    }
}
