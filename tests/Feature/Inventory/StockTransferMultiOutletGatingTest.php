<?php

namespace Tests\Feature\Inventory;

use App\Constants\FlashDataVariable;
use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\StockTransferMode;
use App\Enums\StockTransferStatus;
use App\Enums\SubscriptionStatus;
use App\Helpers\SelectedOutlet;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockTransfer;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockTransferMultiOutletGatingTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected User $user;

    protected InventoryItem $item;

    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Multi Outlet Test Business',
            'owner_name' => 'Owner',
            'email' => 'tf_multi_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Pusat',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $this->user->givePermissionTo(Permission::all());

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();
        $this->item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Barang Transfer',
            'uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
    }

    public function test_single_outlet_business_redirects_away_from_transfers_with_flash_error(): void
    {
        // Currently only 1 outlet (Outlet Pusat) is active
        $this->assertEquals(1, $this->business->outlets()->where('is_active', true)->count());

        $domain = config('domain.app', 'app.sollu.test');
        $response = $this->actingAs($this->user)
            ->get("http://{$domain}/inventories/transfers");

        $response->assertRedirect(route('inventories.stocks.index'));
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Fitur Mutasi Stok membutuhkan minimal 2 outlet aktif.');
    }

    public function test_multi_outlet_business_allows_transfers_index_access(): void
    {
        // Add second active outlet
        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $domain = config('domain.app', 'app.sollu.test');
        $response = $this->actingAs($this->user)
            ->get("http://{$domain}/inventories/transfers");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Transfer/Index')
            ->has('transfers')
            ->has('filters')
        );
    }

    public function test_transfers_scoping_by_incoming_outgoing_and_all(): void
    {
        // Add second active outlet
        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->user->outlets()->attach([$this->outletA->id, $this->outletB->id]);

        // Transfer 1: Outgoing from Outlet A to Outlet B
        $tfOutgoing = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-OUT-001',
            'transfer_mode' => StockTransferMode::DIRECT,
            'status' => StockTransferStatus::Completed,
            'requested_by' => $this->user->id,
        ]);

        // Transfer 2: Incoming to Outlet A from Outlet B
        $tfIncoming = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletB->id,
            'to_outlet_id' => $this->outletA->id,
            'transfer_number' => 'TF-IN-002',
            'transfer_mode' => StockTransferMode::DIRECT,
            'status' => StockTransferStatus::Completed,
            'requested_by' => $this->user->id,
        ]);

        $domain = config('domain.app', 'app.sollu.test');

        // Select Outlet A in session
        $sessionKey = SelectedOutlet::SESSION_KEY_PREFIX.$this->user->id;

        // 1. Filter: incoming
        $responseIncoming = $this->actingAs($this->user)
            ->withSession([$sessionKey => $this->outletA->id])
            ->get("http://{$domain}/inventories/transfers?transfer_type=incoming");

        $responseIncoming->assertOk();
        $responseIncoming->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Transfer/Index')
            ->has('transfers.data', 1)
            ->where('transfers.data.0.id', $tfIncoming->id)
        );

        // 2. Filter: outgoing
        $responseOutgoing = $this->actingAs($this->user)
            ->withSession([$sessionKey => $this->outletA->id])
            ->get("http://{$domain}/inventories/transfers?transfer_type=outgoing");

        $responseOutgoing->assertOk();
        $responseOutgoing->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Transfer/Index')
            ->has('transfers.data', 1)
            ->where('transfers.data.0.id', $tfOutgoing->id)
        );

        // 3. Filter: all
        $responseAll = $this->actingAs($this->user)
            ->withSession([$sessionKey => $this->outletA->id])
            ->get("http://{$domain}/inventories/transfers?transfer_type=all");

        $responseAll->assertOk();
        $responseAll->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Transfer/Index')
            ->has('transfers.data', 2)
        );
    }
}
