<?php

namespace Tests\Feature\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\InventoryItem;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductInventoryMultiOutletTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected Outlet $outletC;

    protected \App\Models\Uom $uom;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $this->uom = \App\Models\Uom::first();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::PRODUCT_CATALOG->value]]
        );

        $this->business = Business::create([
            'name' => 'Multi Outlet Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::PRODUCT_CATALOG->value],
            ],
        ]);

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet A (Pusat)',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet B (Cabang)',
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet C (Gudang)',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Manager All Outlets',
            'email' => 'manager_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->sync([
            $this->outletA->id,
            $this->outletB->id,
            $this->outletC->id,
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::PRODUCT_CREATE->value,
            PermissionEnum::PRODUCT_UPDATE->value,
            PermissionEnum::PRODUCT_DELETE->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_creating_product_in_outlet_a_only_creates_inventory_balance_for_outlet_a(): void
    {
        $payload = [
            'name' => 'Kopi Susu Gula Aren',
            'code' => 'KOP-AREN',
            'product_type' => 'basic',
            'base_price' => 18000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'min_stock' => 10,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                [
                    'outlet_id' => $this->outletA->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ],
                [
                    'outlet_id' => $this->outletB->id,
                    'is_enabled' => false,
                    'is_available' => false,
                ],
                [
                    'outlet_id' => $this->outletC->id,
                    'is_enabled' => false,
                    'is_available' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/products", $payload);

        $response->assertRedirect(route('master.products.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $product = Product::where('business_id', $this->business->id)
            ->where('code', 'KOP-AREN')
            ->firstOrFail();

        $this->assertTrue($product->track_inventory);

        $inventoryItem = InventoryItem::where('product_id', $product->id)->firstOrFail();

        // Saldo hanya boleh terbuat untuk Outlet A
        $this->assertDatabaseHas('inventory_balances', [
            'business_id' => $this->business->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletA->id,
            'current_stock' => 0,
        ]);

        // Outlet B dan C TIDAK boleh memiliki record saldo
        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletC->id,
        ]);

        $this->assertEquals(1, InventoryBalance::where('inventory_item_id', $inventoryItem->id)->count());
    }

    public function test_enabling_product_in_outlet_b_automatically_creates_inventory_balance_for_outlet_b(): void
    {
        // 1. Buat produk awal di Outlet A saja
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Teh Tarik',
            'code' => 'TEH-TARIK',
            'product_type' => 'basic',
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);

        $product->outlets()->sync([
            $this->outletA->id => ['is_enabled' => true, 'is_available' => true],
            $this->outletB->id => ['is_enabled' => false, 'is_available' => false],
        ]);

        $inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Teh Tarik',
            'sku' => 'TEH-TARIK',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'min_stock' => 5,
        ]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 20,
        ]);

        // Pastikan Outlet B belum punya saldo
        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletB->id,
        ]);

        // 2. Update produk mengaktifkan Outlet B
        $updatePayload = [
            'name' => 'Teh Tarik',
            'code' => 'TEH-TARIK',
            'product_type' => 'basic',
            'base_price' => 15000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'outlets' => [
                [
                    'outlet_id' => $this->outletA->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ],
                [
                    'outlet_id' => $this->outletB->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);

        $response->assertRedirect(route('master.products.index'));

        // 3. Verifikasi Outlet B otomatis dibuatkan saldo dengan current_stock = 0
        $this->assertDatabaseHas('inventory_balances', [
            'business_id' => $this->business->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletB->id,
            'current_stock' => 0,
        ]);

        // Saldo Outlet A tetap ada dan nilainya tidak ter-reset
        $this->assertDatabaseHas('inventory_balances', [
            'business_id' => $this->business->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $this->outletA->id,
            'current_stock' => 20,
        ]);
    }

    public function test_disabling_outlet_a_does_not_delete_historical_inventory_balance_and_movements(): void
    {
        // 1. Buat produk dengan saldo dan riwayat mutasi di Outlet A
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Matcha Latte',
            'code' => 'MAT-001',
            'product_type' => 'basic',
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);

        $product->outlets()->sync([
            $this->outletA->id => ['is_enabled' => true, 'is_available' => true],
            $this->outletB->id => ['is_enabled' => true, 'is_available' => true],
        ]);

        $inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'name' => 'Matcha Latte',
            'sku' => 'MAT-001',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'min_stock' => 5,
        ]);

        $balanceA = InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 15,
        ]);

        $movement = InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inventoryItem->id,
            'movement_type' => InventoryMovementType::InitialStock->value,
            'qty_change' => 15,
            'stock_before' => 0,
            'stock_after' => 15,
            'description' => 'Stok awal sebelum dinonaktifkan',
        ]);

        $balanceB = InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletB->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
        ]);

        // 2. Nonaktifkan Outlet A (is_enabled = false)
        $updatePayload = [
            'name' => 'Matcha Latte',
            'code' => 'MAT-001',
            'product_type' => 'basic',
            'base_price' => 25000,
            'track_inventory' => true,
            'uom_id' => $this->uom->id,
            'outlets' => [
                [
                    'outlet_id' => $this->outletA->id,
                    'is_enabled' => false,
                    'is_available' => false,
                ],
                [
                    'outlet_id' => $this->outletB->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/products/{$product->id}", $updatePayload);

        $response->assertRedirect(route('master.products.index'));

        // 3. Verifikasi outlet_product berstatus is_enabled = false
        $this->assertDatabaseHas('outlet_product', [
            'outlet_id' => $this->outletA->id,
            'product_id' => $product->id,
            'is_enabled' => false,
        ]);

        // 4. Verifikasi InventoryBalance dan InventoryMovement di Outlet A TIDAK DIHAPUS (Historical value preserved)
        $this->assertDatabaseHas('inventory_balances', [
            'id' => $balanceA->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 15,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $inventoryItem->id,
            'qty_change' => 15,
        ]);
    }
}
