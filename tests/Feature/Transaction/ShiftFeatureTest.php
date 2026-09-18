<?php

namespace Tests\Feature\Transaction;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\ShiftCashLogType;
use App\Enums\ShiftStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Sales\Shift;
use App\Models\Sales\ShiftCashLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShiftFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

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

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::SHIFT_MANAGEMENT->value]]
        );

        $this->business = Business::create([
            'name' => 'Test Shift Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::SHIFT_MANAGEMENT->value],
            ],
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Kasir Utama',
            'email' => 'kasir_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->sync([$this->outlet->id]);

        setPermissionsTeamId($this->business->id);

        Permission::firstOrCreate(['name' => PermissionEnum::TRANSACTION_VIEW->value, 'guard_name' => 'business']);
        $this->user->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);
    }

    public function test_guest_cannot_access_shifts(): void
    {
        $response = $this->get("http://{$this->appDomain}/transactions/shifts");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_shifts(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
    }

    public function test_authorized_user_can_view_shifts_page(): void
    {
        $shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-20260919-001',
            'opening_cash' => 200000,
            'closing_cash' => 500000,
            'expected_cash' => 500000,
            'total_sales' => 300000,
            'status' => ShiftStatus::Closed,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Shift/Index')
            ->has('shifts.data', 1)
            ->where('shifts.data.0.id', $shift->id)
            ->where('shifts.data.0.opening_cash', 200000)
            ->has('filters')
        );
    }

    public function test_shifts_can_be_filtered_by_status(): void
    {
        Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-OPEN-001',
            'opening_cash' => 100000,
            'status' => ShiftStatus::Open,
        ]);

        Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-CLOSED-002',
            'opening_cash' => 150000,
            'closing_cash' => 350000,
            'expected_cash' => 350000,
            'status' => ShiftStatus::Closed,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts?status=open");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Shift/Index')
            ->has('shifts.data', 1)
            ->where('shifts.data.0.shift_number', 'SH-OPEN-001')
        );
    }

    public function test_shifts_can_be_filtered_by_search(): void
    {
        Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-TARGET-001',
            'opening_cash' => 100000,
            'status' => ShiftStatus::Open,
        ]);

        Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-OTHER-002',
            'opening_cash' => 100000,
            'status' => ShiftStatus::Open,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts?search=TARGET");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Shift/Index')
            ->has('shifts.data', 1)
            ->where('shifts.data.0.shift_number', 'SH-TARGET-001')
        );
    }

    public function test_tenant_isolation_cannot_access_other_business_shifts(): void
    {
        $otherType = BusinessType::first();
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $otherType->id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Outlet Tetangga',
            'is_active' => true,
        ]);

        $otherUser = User::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Kasir Lain',
            'email' => 'other_kasir_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $otherShift = Shift::create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $otherUser->id,
            'shift_number' => 'SH-SECRET-999',
            'opening_cash' => 500000,
            'status' => ShiftStatus::Open,
        ]);

        // 1. Other business shift should not appear in current merchant's index
        $indexResponse = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts");

        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Shift/Index')
            ->has('shifts.data', 0)
        );

        // 2. Current merchant cannot view other merchant's shift detail
        $showResponse = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts/{$otherShift->id}");

        $showResponse->assertStatus(403);
    }

    public function test_show_shift_returns_json_resource_when_requested(): void
    {
        $shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-2026-JSON',
            'opening_cash' => 150000,
            'closing_cash' => 350000,
            'expected_cash' => 350000,
            'total_sales' => 200000,
            'status' => ShiftStatus::Closed,
            'closed_at' => now(),
        ]);

        ShiftCashLog::create([
            'shift_id' => $shift->id,
            'type' => ShiftCashLogType::CashIn,
            'amount' => 50000,
            'description' => 'Tambah modal kas',
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/shifts/{$shift->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'shift_number',
                'opening_cash',
                'closing_cash',
                'expected_cash',
                'total_sales',
                'status',
                'user' => ['id', 'name', 'email'],
                'outlet' => ['id', 'name'],
                'cash_logs' => [
                    '*' => ['id', 'type', 'amount', 'description', 'created_at'],
                ],
            ],
        ]);

        $this->assertEquals(150000.0, $response->json('data.opening_cash'));
        $this->assertEquals(50000.0, $response->json('data.cash_logs.0.amount'));
        $this->assertEquals('cash_in', $response->json('data.cash_logs.0.type'));
    }

    public function test_show_shift_renders_inertia_page(): void
    {
        $shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'shift_number' => 'SH-PAGE-001',
            'opening_cash' => 250000,
            'status' => ShiftStatus::Open,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/shifts/{$shift->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Shift/Show')
            ->where('shift.id', $shift->id)
            ->where('shift.shift_number', 'SH-PAGE-001')
        );
    }
}
