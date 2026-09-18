<?php

namespace Tests\Unit\Services\App\Reports;

use App\Models\Outlet;
use App\Models\Sales\Shift;
use App\Models\User;
use App\Services\App\Reports\CashierShiftReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierShiftReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CashierShiftReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CashierShiftReportService;
    }

    public function test_it_gets_report()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Report Merchant',
            'owner_name' => 'Report Owner',
            'email' => 'report_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Cashier Test User',
            'email' => 'cashier_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Report',
        ]);

        $shift = Shift::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'shift_number' => 'SHIFT-001',
            'status' => 'closed',
            'opening_cash' => 100000,
            'expected_cash' => 150000,
            'closing_cash' => 145000,
            'closed_at' => now(),
        ]);

        $startDate = Carbon::now()->subDays(1);
        $endDate = Carbon::now()->addDays(1);

        $result = $this->service->getReport($outlet->id, $startDate, $endDate);

        $this->assertNotEmpty($result->items());
        $firstItem = $result->items()[0];

        $this->assertEquals($shift->id, $firstItem->id);
        $this->assertEquals($user->name, $firstItem->cashier_name);
        $this->assertEquals(100000, $firstItem->starting_cash);
        $this->assertEquals(150000, $firstItem->expected_ending_cash);
        $this->assertEquals(145000, $firstItem->actual_ending_cash);
        $this->assertEquals(-5000, $firstItem->difference);
    }
}
