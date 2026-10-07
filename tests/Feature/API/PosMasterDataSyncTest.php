<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\Sales\TransactionPayment;
use App\Models\Uom;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosMasterDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected OutletDevice $deviceA;

    protected User $employeeA;

    protected User $employeeB;

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
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Kemang',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Senopati',
            'is_active' => true,
        ]);

        $this->deviceA = OutletDevice::create([
            'outlet_id' => $this->outletA->id,
            'device_name' => 'POS Kemang 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'dev-uuid-001',
            'hardware_fingerprint' => 'hw-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->deviceA);

        setPermissionsTeamId($this->business->id);

        // Karyawan terdaftar di Outlet A
        $this->employeeA = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'name' => 'Budi Kasir Kemang',
            'email' => 'budi_kemang_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('123456'),
        ]);
        $this->outletA->users()->attach($this->employeeA->id);
        $this->employeeA->givePermissionTo(PermissionEnum::TRANSACTION_CREATE->value);
        $this->employeeA->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);

        // Karyawan terdaftar di Outlet B (Hanya di outlet B!)
        $this->employeeB = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletB->id,
            'name' => 'Siti Kasir Senopati',
            'email' => 'siti_senopati_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('654321'),
        ]);
        $this->outletB->users()->attach($this->employeeB->id);
    }

    public function test_sync_master_data_returns_employees_with_role_and_permissions_strictly_scoped_to_outlet(): void
    {
        Sanctum::actingAs($this->deviceA, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('employees', $data);

        $employees = $data['employees'];
        $this->assertCount(1, $employees);
        $this->assertEquals($this->employeeA->id, $employees[0]['id']);
        $this->assertEquals('Budi Kasir Kemang', $employees[0]['name']);
        $this->assertNotNull($employees[0]['role']);
        $this->assertContains(PermissionEnum::TRANSACTION_CREATE->value, $employees[0]['permissions']);
        $this->assertContains(PermissionEnum::TRANSACTION_VIEW->value, $employees[0]['permissions']);

        // Pastikan karyawan outlet B TIDAK bocor ke outlet A
        $employeeIds = array_column($employees, 'id');
        $this->assertNotContains($this->employeeB->id, $employeeIds);
    }

    public function test_employees_endpoint_returns_permissions_and_strictly_scoped_to_outlet(): void
    {
        Sanctum::actingAs($this->deviceA, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/employees');

        $response->assertStatus(200);

        $employees = $response->json('data');
        $this->assertCount(1, $employees);
        $this->assertEquals($this->employeeA->id, $employees[0]['id']);
        $this->assertContains(PermissionEnum::TRANSACTION_CREATE->value, $employees[0]['permissions']);
        $this->assertTrue(Cache::has("pos:outlet:{$this->outletA->id}:employees"));

        $updatePinResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->putJson('http://api.sollu.test/pos/employees/pin', [
            'user_id' => $this->employeeA->id,
            'current_pin' => '123456',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $updatePinResponse->assertStatus(200);
        $this->assertFalse(Cache::has("pos:outlet:{$this->outletA->id}:employees"));
    }

    public function test_sync_master_data_succeeds_when_outlet_has_existing_transactions_and_items(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Latte',
            'code' => 'KOP-001',
            'product_type' => 'basic',
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outletA->id, ['is_enabled' => true, 'is_available' => true]);

        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Cash',
            'type' => 'cash',
        ]);
        $this->outletA->paymentMethods()->attach($pm->id, ['is_enabled' => true]);

        $trx = Transaction::create([
            'outlet_id' => $this->outletA->id,
            'customer_id' => null,
            'transaction_number' => 'TRX-POS-1001',
            'transaction_date' => now(),
            'channel' => SalesChannelEnum::Direct,
            'type' => TransactionTypeEnum::Pos,
            'payment_status' => TransactionPaymentStatus::Paid,
            'status' => TransactionStatus::Completed,
            'subtotal' => 25000,
            'total' => 25000,
            'total_paid' => 25000,
            'balance_due' => 0,
        ]);

        $item = TransactionItem::create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_name' => 'Kopi Latte',
            'price' => 25000,
            'qty' => 1,
            'subtotal' => 25000,
        ]);

        TransactionPayment::create([
            'transaction_id' => $trx->id,
            'payment_method_id' => $pm->id,
            'amount' => 25000,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);

        Sanctum::actingAs($this->deviceA, ['pos:access']);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('transactions', $data);
        $this->assertArrayNotHasKey('transaction_items', $data);
        $this->assertArrayNotHasKey('transaction_payments', $data);
        $this->assertArrayNotHasKey('transaction_item_modifiers', $data);

        // Pastikan master data tetap lengkap
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('payment_methods', $data);
        $this->assertArrayHasKey('employees', $data);
        $this->assertCount(1, $data['products']);
        $this->assertEquals($product->id, $data['products'][0]['id']);
    }

    public function test_sync_master_data_includes_unit_and_service_products(): void
    {
        Sanctum::actingAs($this->deviceA, ['pos:access']);

        $uom = Uom::firstOrCreate(
            ['code' => 'Box'],
            ['name' => 'Box', 'category' => 'package']
        );

        $physicalProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Robusta',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);
        $physicalProduct->outlets()->attach($this->outletA->id, ['is_enabled' => true, 'is_available' => true]);

        $productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $physicalProduct->id,
            'name' => 'Kopi Robusta',
            'item_type' => 'variant_sku',
            'uom_id' => $uom->id,
            'is_show' => true,
            'sellable' => true,
            'is_active' => true,
        ]);

        $inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $productItem->id,
            'name' => 'Kopi Robusta',
            'uom_id' => $uom->id,
            'is_active' => true,
        ]);

        $serviceProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Jasa Cuci Kendaraan',
            'product_type' => ProductTypeEnum::SERVICE,
            'is_show' => true,
            'sellable' => true,
        ]);
        $serviceProduct->outlets()->attach($this->outletA->id, ['is_enabled' => true, 'is_available' => true]);

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master?force=1');

        $response->assertStatus(200);
        $data = $response->json('data');

        $products = collect($data['products']);
        $invItems = collect($data['inventory_items']);

        // Pastikan produk layanan dan fisik keduanya ada di data products
        $this->assertTrue($products->contains('id', $serviceProduct->id));
        $this->assertTrue($products->contains('id', $physicalProduct->id));

        $syncedPhysical = $products->firstWhere('id', $physicalProduct->id);
        $syncedService = $products->firstWhere('id', $serviceProduct->id);
        $syncedInv = $invItems->firstWhere('id', $inventoryItem->id);

        $this->assertEquals('service', $syncedService['product_type']);
        $this->assertEquals('basic', $syncedPhysical['product_type']);

        $this->assertEquals('Box', $syncedPhysical['unit']);
        $this->assertNotNull($syncedInv);
        $this->assertEquals('Box', $syncedInv['unit']);
    }

    public function test_sync_master_data_executes_minimal_queries_without_duplicates(): void
    {
        Sanctum::actingAs($this->deviceA, ['pos:access']);

        // Panggilan awal untuk memastikan auto-provisioning selesai
        $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'dev-uuid-001',
            'X-HARDWARE-SIGNATURE' => 'hw-sig-001',
        ])->getJson('http://api.sollu.test/pos/sync/master');

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $sqls = array_column($queries, 'query');

        // Check for duplicate identical queries
        $duplicateQueries = array_filter(array_count_values($sqls), fn ($count) => $count > 1);
        $this->assertEmpty($duplicateQueries, 'Terdeteksi query duplikat: '.json_encode(array_keys($duplicateQueries)));

        // Pastikan total query berkurang signifikan (di bawah 10 query dibanding 36 sebelumnya)
        $this->assertLessThanOrEqual(9, count($queries), 'Query count melebihi target optimasi ('.count($queries).' queries).');
    }
}
