<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosVoidTransactionTest extends TestCase
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
            'email' => 'pos_void_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'POS Outlet Utama',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789',
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
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_OVERRIDE_PRICE->value);

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
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $this->product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 8.0,
            'allocated_stock' => 0.0,
            'total_value' => 160000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'purchase_price' => 20000,
            'qty_purchased' => 10.0,
            'qty_remaining' => 8.0,
        ]);
    }

    protected function createCompletedTransaction(string $id): Transaction
    {
        $transaction = Transaction::create([
            'id' => $id,
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'sales_channel' => SalesChannelEnum::POS->value,
            'transaction_number' => 'POS/TEST/'.now()->format('Ymd').'/0001',
            'transaction_date' => now(),
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total' => 50000,
            'payment_status' => 'paid',
            'status' => TransactionStatus::Completed->value,
            'created_at' => now(),
        ]);

        $transaction->items()->create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'product_name' => 'Kopi Arabika 250g',
            'price' => 25000,
            'qty' => 2,
            'discount_amount' => 0,
            'subtotal' => 50000,
        ]);

        $transaction->payments()->create([
            'payment_method_id' => $this->cashMethod->id,
            'amount' => 50000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        return $transaction;
    }

    public function test_supervisor_can_successfully_void_current_shift_transaction_restoring_stock(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $txId = (string) Str::uuid();
        $transaction = $this->createCompletedTransaction($txId);

        $payload = [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '998877',
            'reason' => 'Customer requested cancellation',
            'notes' => 'Mistake during input',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Transaksi berstatus void dan voided_at tercatat
        $this->assertDatabaseHas('transactions', [
            'id' => $txId,
            'status' => TransactionStatus::Void->value,
        ]);

        $transaction->refresh();
        $this->assertNotNull($transaction->voided_at);

        // Stok kembali dipulihkan dari 8 pcs ke 10 pcs
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(10.0, (float) $balance->current_stock);
    }

    public function test_void_transaction_fails_with_invalid_supervisor_pin(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $txId = (string) Str::uuid();
        $this->createCompletedTransaction($txId);

        $payload = [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '000000', // Invalid PIN
            'reason' => 'Unauthorized void attempt',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $this->assertContains($response->status(), [401, 403, 422]);

        // Status transaksi harus tetap completed
        $this->assertDatabaseHas('transactions', [
            'id' => $txId,
            'status' => TransactionStatus::Completed->value,
        ]);

        // Stok tidak berubah
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(8.0, (float) $balance->current_stock);
    }

    public function test_void_transaction_fails_when_user_lacks_supervision_permission(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $txId = (string) Str::uuid();
        $this->createCompletedTransaction($txId);

        // Kasir biasa tanpa izin supervisi mencoba melakukan void
        $payload = [
            'supervisor_id' => $this->cashier->id,
            'supervisor_pin' => '112233',
            'reason' => 'Cashier trying to void directly',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $this->assertContains($response->status(), [403, 422]);

        // Status transaksi tetap completed
        $this->assertDatabaseHas('transactions', [
            'id' => $txId,
            'status' => TransactionStatus::Completed->value,
        ]);
    }

    public function test_void_transaction_fails_when_supervisor_belongs_to_another_business(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $txId = (string) Str::uuid();
        $this->createCompletedTransaction($txId);

        // Buat business dan supervisor lain (cross-tenant attack)
        $otherBusiness = Business::create([
            'name' => 'Competitor Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_biz_'.uniqid().'@test.test',
            'phone' => '089999999999',
            'status' => 'active',
            'business_type_id' => $this->business->business_type_id,
        ]);

        $foreignSupervisor = User::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign Supervisor',
            'email' => 'foreign_spv_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('123456'),
        ]);

        $payload = [
            'supervisor_id' => $foreignSupervisor->id,
            'supervisor_pin' => '123456',
            'reason' => 'Cross-tenant supervisor void attack',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $this->assertContains($response->status(), [403, 422]);
        $response->assertJsonPath('status', 'error');

        // Status transaksi harus tetap completed
        $this->assertDatabaseHas('transactions', [
            'id' => $txId,
            'status' => TransactionStatus::Completed->value,
        ]);
    }

    public function test_void_transaction_fails_if_already_voided(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $txId = (string) Str::uuid();
        $transaction = $this->createCompletedTransaction($txId);
        $transaction->update([
            'status' => TransactionStatus::Void->value,
            'voided_at' => now(),
        ]);

        $payload = [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '998877',
            'reason' => 'Attempting duplicate void',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-001',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $this->assertContains($response->status(), [400, 422]);
    }

    public function test_void_transaction_fails_for_cross_outlet_device(): void
    {
        $otherOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang Lain',
            'is_active' => true,
        ]);

        $otherDevice = OutletDevice::create([
            'outlet_id' => $otherOutlet->id,
            'device_name' => 'POS Terminal Cabang',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-dev-other',
            'hardware_fingerprint' => 'pos-sig-other',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($otherDevice);

        Sanctum::actingAs($otherDevice, ['pos:access']);

        $txId = (string) Str::uuid();
        $this->createCompletedTransaction($txId);

        $payload = [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '998877',
            'reason' => 'Cross outlet void attack',
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-other',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-other',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", $payload);

        $this->assertContains($response->status(), [403, 404]);
    }
}
