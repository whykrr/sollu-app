<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\Uom;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosTransactionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected OutletDevice $device;

    protected User $cashier;

    protected User $supervisor;

    protected Shift $shift;

    protected PaymentMethod $cashMethod;

    protected InventoryItem $inventoryItem;

    protected Product $product;

    protected PosDeviceAuthCacheService $cacheService;

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
            'name' => 'POS Merchant Test',
            'owner_name' => 'Merchant Owner',
            'email' => 'pos_merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'POS Outlet Utama',
            'is_active' => true,
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'POS Terminal 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-dev-001',
            'hardware_fingerprint' => 'pos-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        setPermissionsTeamId($this->business->id);

        $this->cashier = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Kasir Utama',
            'email' => 'kasir_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('112233'),
        ]);
        $this->outlet->users()->attach($this->cashier->id);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_CREATE->value);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_OPEN_DRAWER->value);

        $this->supervisor = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Supervisor Toko',
            'email' => 'spv_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('998877'),
        ]);
        $this->outlet->users()->attach($this->supervisor->id);
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value);
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_DISCOUNT->value);

        $this->shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->cashier->id,
            'shift_number' => 'SH-001',
            'start_time' => now()->subHours(2),
            'opening_balance' => 100000,
            'status' => 'open',
        ]);

        $this->cashMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai / Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);
        $this->cashMethod->outlets()->attach($this->outlet->id);

        $uom = Uom::where('code', 'Pcs')->first()
            ?? Uom::first()
            ?? Uom::create(['code' => 'Pcs', 'name' => 'Pieces']);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika 250g',
            'sku' => 'KOP-001',
            'uom_id' => $uom->id,
            'cost_method' => 'fifo',
            'average_cost' => 20000,
            'last_cost' => 20000,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika 250g',
            'code' => 'KOP-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'is_show' => true,
            'sellable' => true,
        ]);
        $this->product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        // Berikan saldo awal inventori 10 pcs @ 20.000
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 10.0,
            'allocated_stock' => 0.0,
            'total_value' => 200000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'purchase_price' => 20000,
            'qty_purchased' => 10.0,
            'qty_remaining' => 10.0,
            'created_at' => now()->subDays(1),
        ]);
    }

    public function test_sync_offline_transaction_successfully_creates_record_and_deducts_fifo_stock(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $payload = [
            'transaction_number' => 'TRX/POS/202610/0001',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 50000,
            'discount_amount' => 5000,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 45000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Pembelian langsung POS',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'product_name' => 'Kopi Arabika 250g',
                    'price' => 25000,
                    'qty' => 2,
                    'discount_amount' => 5000,
                    'subtotal' => 45000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 50000,
                    'change_amount' => 5000,
                    'payment_reference' => 'CASH-001',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Transaksi berhasil disinkronisasi');
        $this->assertNotEmpty($response->json('data.transaction.id'));

        // Pastikan transaksi tercatat di database
        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'TRX/POS/202610/0001',
            'total' => 45000,
            'status' => TransactionStatus::Completed->value,
            'channel' => SalesChannelEnum::Direct->value,
        ]);

        // Pastikan item tercatat dengan inventory_item_id yang sesuai
        $this->assertDatabaseHas('transaction_items', [
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 2,
            'price' => 25000,
        ]);

        // Pastikan stok berkurang dari 10 menjadi 8
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(8.0, (float) $balance->current_stock);

        // Pastikan layer FIFO berkurang
        $layer = InventoryCostLayer::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(8.0, (float) $layer->qty_remaining);
    }

    public function test_sync_offline_transaction_is_strictly_idempotent(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $payload = [
            'transaction_number' => 'TRX/POS/202610/0002',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 25000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 25000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'product_name' => 'Kopi Arabika 250g',
                    'price' => 25000,
                    'qty' => 1,
                    'discount_amount' => 0,
                    'subtotal' => 25000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 25000,
                    'change_amount' => 0,
                ],
            ],
        ];

        // Pengiriman pertama
        $res1 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);
        $res1->assertStatus(200);

        // Pengiriman kedua dengan nomor transaksi yang sama persis
        $res2 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);
        $res2->assertStatus(200);

        // Pastikan hanya 1 record transaksi di database
        $count = Transaction::where('transaction_number', 'TRX/POS/202610/0002')->count();
        $this->assertEquals(1, $count);

        // Pastikan stok hanya berkurang 1 (dari 10 menjadi 9), BUKAN terpotong dua kali!
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(9.0, (float) $balance->current_stock);
    }

    public function test_master_data_and_employees_endpoints_provide_supervision_and_open_drawer_permissions(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master');

        $response->assertStatus(200);
        $employees = $response->json('data.employees');
        $this->assertNotEmpty($employees);

        // Verifikasi supervisor memiliki permission transaction.validation_supervision
        $spvData = collect($employees)->firstWhere('id', $this->supervisor->id);
        $this->assertNotNull($spvData);
        $this->assertContains(
            PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value,
            $spvData['permissions']
        );

        // Verifikasi kasir memiliki permission transaction.open_drawer
        $cashierData = collect($employees)->firstWhere('id', $this->cashier->id);
        $this->assertNotNull($cashierData);
        $this->assertContains(
            PermissionEnum::TRANSACTION_OPEN_DRAWER->value,
            $cashierData['permissions']
        );
    }

    public function test_transaction_sync_supports_mutation_log_deduction_and_offline_id_preservation(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $offlineId = (string) Str::uuid();
        $payload = [
            'offline_id' => $offlineId,
            'transaction_number' => 'POS/MUT/20261009/0001',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 25000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 25000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'product_name' => 'Kopi Susu',
                    'price' => 25000,
                    'qty_deducted' => 3.0, // Format log mutasi kuantitas
                    'discount_amount' => 0,
                    'subtotal' => 25000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 25000,
                    'change_amount' => 0,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);

        $response->assertStatus(200);

        // Verifikasi ID transaksi server sama dengan offline_id
        $this->assertDatabaseHas('transactions', [
            'id' => $offlineId,
            'transaction_number' => 'POS/MUT/20261009/0001',
        ]);

        // Verifikasi stok terpotong sebesar 3 (10 - 3 = 7)
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(7.0, (float) $balance->current_stock);

        // Verifikasi ledger InventoryMovement tercatat
        $movement = InventoryMovement::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->where('reference_id', $offlineId)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-3.0, (float) $movement->qty_change);
    }
}
