<?php

namespace Tests\Unit\Services\App\Outlet;

use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Outlet\ManageOutletOperationalHourService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageOutletOperationalHourServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManageOutletOperationalHourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ManageOutletOperationalHourService;
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        return User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);
    }

    public function test_it_upserts_operational_hours()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Test',
        ]);

        // Pre-create Monday to simulate update
        $outlet->operationalHours()->create([
            'day_of_week' => 1,
            'open_time' => '08:00:00',
            'close_time' => '17:00:00',
            'is_closed' => false,
        ]);

        $hours = [
            [
                'day_of_week' => 1, // Monday
                'open_time' => '09:00:00',
                'close_time' => '18:00:00',
                'is_closed' => false,
            ],
            [
                'day_of_week' => 2, // Tuesday
                'open_time' => null,
                'close_time' => null,
                'is_closed' => true,
            ],
        ];

        $result = $this->service->upsertHours($outlet, $hours, $user);

        $this->assertCount(2, $result);

        $this->assertDatabaseHas('outlet_operational_hours', [
            'outlet_id' => $outlet->id,
            'day_of_week' => 1,
            'open_time' => '09:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false,
        ]);

        $this->assertDatabaseHas('outlet_operational_hours', [
            'outlet_id' => $outlet->id,
            'day_of_week' => 2,
            'is_closed' => true,
        ]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'action' => 'operational_hours_updated',
        ]);
    }
}
