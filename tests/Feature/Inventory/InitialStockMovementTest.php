<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Jobs\Inventory\ImportStockJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialStockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected InventoryItem $item;

    protected InventoryBalance $balance;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_MOVEMENTS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Inventory Test Biz',
            'owner_name' => 'Biz Owner',
            'email' => 'biz_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_MOVEMENTS->value,
                ],
            ],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'code' => 'OUT-01',
            'address' => 'Jl. Test No. 1',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Inventory Staff',
            'email' => 'staff_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $uom = Uom::where('code', 'PCS')->orWhere('code', 'Pcs')->orWhere('code', 'pcs')->first()
            ?? Uom::create(['code' => 'PCS', 'name' => 'Pieces']);

        $this->item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Bahan Baku Uji',
            'sku' => 'SKU-TEST-001',
            'uom_id' => $uom->id,
            'item_type' => 'raw_material',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->balance = InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_stock' => 0,
        ]);

        $this->subscribeBusinessToPlan($this->user);
    }

    protected function subscribeBusinessToPlan(User $user): void
    {
        setPermissionsTeamId($user->business_id);
        $plan = \App\Models\SubscriptionPlan::firstOrCreate(
            ['code' => 'pro'],
            ['name' => 'Pro Plan', 'is_active' => true]
        );

        \App\Models\Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDays(1),
            'expired_at' => now()->addDays(29),
        ]);

        $business = $user->business;
        $settings = $business->settings ?? [];
        $settings['active_features'] = array_map(fn (FeatureEnum $case) => $case->value, FeatureEnum::cases());
        $business->settings = $settings;
        $business->save();

        $user->refresh();
    }

    public function test_store_initial_stock_creates_movement_with_initial_stock_type(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/stocks/{$this->balance->id}/initial-stock", [
                'qty' => 50,
                'purchase_price' => 12500,
            ]);

        $response->assertStatus(200);

        $this->balance->refresh();
        $this->assertEquals(50, $this->balance->current_stock);

        $movement = InventoryMovement::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovementType::InitialStock, $movement->movement_type);
        $this->assertEquals('initial_stock', $movement->movement_type->value);
        $this->assertEquals('Stok Awal', $movement->movement_type->label());
        $this->assertEquals(50, $movement->qty_change);
        $this->assertEquals(0, $movement->stock_before);
        $this->assertEquals(50, $movement->stock_after);
        $this->assertEquals('Input Stok Awal', $movement->description);
    }

    public function test_import_stock_job_creates_movement_with_initial_stock_type(): void
    {
        $job = new ImportStockJob($this->user, 'dummy_path.csv', $this->business->id);

        $job->processRow([
            'SKU' => $this->item->sku,
            'Nama' => $this->item->name,
            'Outlet' => $this->outlet->name,
            'Stok Awal' => '25',
            'Harga Beli' => '15000',
        ]);

        $this->balance->refresh();
        $this->assertEquals(25, $this->balance->current_stock);

        $movement = InventoryMovement::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovementType::InitialStock, $movement->movement_type);
        $this->assertEquals('initial_stock', $movement->movement_type->value);
        $this->assertEquals('Stok Awal', $movement->movement_type->label());
        $this->assertEquals(25, $movement->qty_change);
        $this->assertEquals('Input Stok Awal (Impor CSV)', $movement->description);
    }
}
