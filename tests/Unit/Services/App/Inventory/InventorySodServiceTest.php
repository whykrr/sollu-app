<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Models\Business;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockOpname;
use App\Models\Inventory\StockTransfer;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Inventory\InventorySodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySodServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventorySodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventorySodService;
    }

    private function createBusinessWithUser(array $sodSettings = []): array
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test SoD Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ! empty($sodSettings) ? ['inventory_sod' => $sodSettings] : [],
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Staff User',
            'email' => 'staff_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        return [$business, $user, $outlet];
    }

    public function test_it_returns_default_settings_when_disabled(): void
    {
        [$business] = $this->createBusinessWithUser();

        $settings = $this->service->getSettings($business);

        $this->assertFalse($settings['enabled']);
        $this->assertTrue($settings['allow_owner_bypass']);
        $this->assertTrue($settings['rules']['stock_adjustment']);
        $this->assertFalse($this->service->isSodEnabled($business));
    }

    public function test_it_allows_self_approval_when_sod_is_globally_disabled(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => false,
        ]);

        $adjustment = new StockAdjustment([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'created_by' => $user->id,
        ]);
        $adjustment->setRelation('business', $business);

        // Should not throw any exception
        $this->service->assertCanApproveAdjustment($adjustment, $user);
        $this->assertTrue(true);
    }

    public function test_it_blocks_self_approval_for_adjustment_when_sod_is_enabled(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => ['stock_adjustment' => true],
        ]);

        $adjustment = new StockAdjustment([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'created_by' => $user->id,
        ]);
        $adjustment->setRelation('business', $business);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemisahan tugas aktif');

        $this->service->assertCanApproveAdjustment($adjustment, $user);
    }

    public function test_owner_can_bypass_when_bypass_is_allowed(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => true,
            'allow_owner_bypass' => true,
            'rules' => ['stock_adjustment' => true],
        ]);

        setPermissionsTeamId($business->id);
        \Spatie\Permission\Models\Permission::findOrCreate('business.*', 'web');
        $user->givePermissionTo('business.*');

        $adjustment = new StockAdjustment([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'created_by' => $user->id,
        ]);
        $adjustment->setRelation('business', $business);

        // Should bypass without exception
        $this->service->assertCanApproveAdjustment($adjustment, $user);
        $this->assertTrue(true);
    }

    public function test_it_evaluates_stock_opname_sod(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => ['stock_opname' => true],
        ]);

        $opname = new StockOpname([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'created_by' => $user->id,
        ]);
        $opname->setRelation('business', $business);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemisahan tugas aktif');

        $this->service->assertCanApproveOpname($opname, $user);
    }

    public function test_it_evaluates_transfer_approval_and_receive_sod(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => [
                'stock_transfer_approval' => true,
                'stock_transfer_receive' => true,
            ],
        ]);

        $outlet2 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Branch Outlet',
            'is_active' => true,
        ]);

        $transfer = new StockTransfer([
            'business_id' => $business->id,
            'from_outlet_id' => $outlet->id,
            'to_outlet_id' => $outlet2->id,
            'requested_by' => $user->id,
        ]);
        $transfer->setRelation('business', $business);

        // 1. Approval check
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kamu tidak dapat menyetujui permintaan transfer stok yang kamu ajukan sendiri');
        $this->service->assertCanApproveTransfer($transfer, $user);
    }

    public function test_it_evaluates_purchase_order_receive_sod(): void
    {
        [$business, $user, $outlet] = $this->createBusinessWithUser([
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => [
                'purchase_order_receive' => true,
            ],
        ]);

        $po = new PurchaseOrder([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'created_by' => $user->id,
        ]);
        $po->setRelation('business', $business);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pembuat pesanan pembelian (PO) tidak dapat mencatat penerimaan fisik surat jalan');
        $this->service->assertCanReceivePurchaseOrder($po, $user);
    }
}
