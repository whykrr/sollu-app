<?php

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\Sales\TransactionPayment;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
