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

        // Other business cashier shift to assert isolation
        $otherBusiness = \App\Models\Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
        ]);

        $otherUser = User::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Cashier',
            'email' => 'cashier_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        Shift::create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $otherUser->id,
            'shift_number' => 'SHIFT-OTHER-001',
            'status' => 'closed',
            'opening_cash' => 500000,
            'expected_cash' => 900000,
            'closing_cash' => 900000,
            'closed_at' => now(),
        ]);

        $startDate = Carbon::now()->subDays(1);
        $endDate = Carbon::now()->addDays(1);

        $result = $this->service->getReport($business->id, [$outlet->id], $startDate, $endDate);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('shifts', $result);

        $summary = $result['summary'];
        $this->assertEquals(1, $summary['total_shifts']);
        $this->assertEquals(150000, $summary['total_expected_cash']);
        $this->assertEquals(145000, $summary['total_closing_cash']);
        $this->assertEquals(-5000, $summary['total_difference']);

        $shifts = $result['shifts'];
        $this->assertNotEmpty($shifts->items());
        $firstItem = $shifts->items()[0];

        $this->assertEquals($shift->id, $firstItem->id);
        $this->assertEquals($user->name, $firstItem->cashier_name);
        $this->assertEquals(100000, $firstItem->starting_cash);
        $this->assertEquals(150000, $firstItem->expected_ending_cash);
        $this->assertEquals(145000, $firstItem->actual_ending_cash);
        $this->assertEquals(-5000, $firstItem->difference);
    }
}
